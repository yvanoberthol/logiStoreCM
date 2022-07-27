<?php


namespace App\Service;

use App\Entity\Supplier;
use App\Entity\Stock;
use App\Entity\StockPayment;
use App\Repository\StockRepository;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SupplierService
{

    /**
     * @var StockRepository
     */
    private $stockRepository;


    /**
     * ProductService constructor.
     * @param StockRepository $stockRepository
     */
    public function __construct(StockRepository $stockRepository)
    {
        $this->stockRepository = $stockRepository;
    }

    public function getCredits(DateTimeInterface $start, DateTimeInterface $end,
                               array $suppliers, $employee=null): array
    {

        $lines = [];
        foreach ($suppliers as $supplier){
            $lines[] = $this->lineCredit($supplier,$start,$end,$employee);
        }

        return array_filter($lines,static function($line){ return $line['amount'] > 0;});
    }

    private function lineCredit(Supplier $supplier,$start,$end,$employee=null): array
    {
        $stocks = $this->stockRepository->findByPeriodSupplier($start,$end,$supplier,$employee);

        $stockCredits = array_filter($stocks,static function(Stock $stock){
          return $stock->getAmountDebt() > 0;
        });

        $amount = array_sum(array_map(static function(Stock $stock){
            return $stock->getAmountDebt();
        },$stockCredits));

        return [
            'id' => $supplier->getId(),
            'name' => $supplier->getName(),
            'type' => $supplier->getType(),
            'amount' => $amount,
        ];
    }


    /**
     * @param Supplier $supplier
     * @param float $amount
     * @return array
     */
    public function getStockPayments(Supplier $supplier, float $amount=0): array {

        $stocksNotSettled = $supplier->getStockNotSettled();


        $stockPaymentResult = [];

        $amountToWithDrawInNextStock = $amount;
        $amountWithdraw=0.0;

        foreach ($stocksNotSettled as $stockNotSettled) {
            $sumAmountDebt = $stockNotSettled->getAmountDebt();

            $stockPayment = new StockPayment();
            $stockPayment->setStock($stockNotSettled);

            if ($amountToWithDrawInNextStock >= $sumAmountDebt){
                $stockPayment->setAmount($sumAmountDebt);
                $amountWithdraw += $sumAmountDebt;
            }else{
                $stockPayment->setAmount($amountToWithDrawInNextStock);
                $amountWithdraw += $amountToWithDrawInNextStock;
            }

            $stockPaymentResult[] = $stockPayment;

            $amountToWithDrawInNextStock = $amount - $amountWithdraw;

            if ($amountToWithDrawInNextStock === 0.0){
                break;
            }
        }


        return $stockPaymentResult;
    }
}
