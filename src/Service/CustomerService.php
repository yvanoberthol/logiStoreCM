<?php


namespace App\Service;


use App\Entity\Attendance;
use App\Entity\Customer;
use App\Entity\Sale;
use App\Entity\SalePayment;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\SaleRepository;
use App\Repository\UserRepository;
use App\Repository\AttendanceRepository;
use App\Util\AttendanceStatusConstant;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CustomerService
{

    /**
     * @var SaleRepository
     */
    private $saleRepository;


    /**
     * ProductService constructor.
     * @param SaleRepository $saleRepository
     */
    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }

    public function getCredits(DateTimeInterface $start, DateTimeInterface $end,
                               array $customers, $employee=null): array
    {

        $lines = [];
        foreach ($customers as $customer){
            $lines[] = $this->lineCredit($customer,$start,$end,$employee);
        }

        return array_filter($lines,static function($line){ return $line['amount'] > 0;});
    }

    private function lineCredit(Customer $customer,$start,$end,$employee=null): array
    {
        $sales = $this->saleRepository->findByPeriodCustomer($start,$end,$customer,$employee);

        $saleCredits = array_filter($sales,static function(Sale $sale){
          return $sale->getAmountDebt() > 0;
        });

        $amount = array_sum(array_map(static function(Sale $sale){
            return $sale->getAmountDebt();
        },$saleCredits));

        return [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'type' => $customer->getType(),
            'amount' => $amount,
        ];
    }


    /**
     * @param Customer $customer
     * @param float $amount
     * @return array
     */
    public function getSalePayments(Customer $customer, float $amount=0): array {

        $salesNotSettled = $customer->getSaleNotSettled();


        $salePaymentResult = [];

        $amountToWithDrawInNextStock = $amount;
        $amountWithdraw=0.0;

        foreach ($salesNotSettled as $saleNotSettled) {
            $sumAmountDebt = $saleNotSettled->getAmountDebt();

            $salePayment = new SalePayment();
            $salePayment->setSale($saleNotSettled);

            if ($amountToWithDrawInNextStock >= $sumAmountDebt){
                $salePayment->setAmount($sumAmountDebt);
                $amountWithdraw += $sumAmountDebt;
            }else{
                $salePayment->setAmount($amountToWithDrawInNextStock);
                $amountWithdraw += $amountToWithDrawInNextStock;
            }

            $salePaymentResult[] = $salePayment;

            $amountToWithDrawInNextStock = $amount - $amountWithdraw;

            if ($amountToWithDrawInNextStock === 0.0){
                break;
            }
        }


        return $salePaymentResult;
    }
}
