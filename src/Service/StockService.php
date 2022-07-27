<?php


namespace App\Service;


use App\Entity\Stock;
use App\Repository\ProductStockRepository;
use App\Repository\StockFeeRepository;

class StockService
{
    /**
     * @var ProductStockRepository
     */
    private $productStockRepository;
    /**
     * @var StockFeeRepository
     */
    private $stockFeeRepository;


    /**
     * OrderService constructor.
     * @param ProductStockRepository $productStockRepository
     * @param StockFeeRepository $stockFeeRepository
     */
    public function __construct(ProductStockRepository $productStockRepository,
                                StockFeeRepository $stockFeeRepository)
    {
        $this->productStockRepository = $productStockRepository;
        $this->stockFeeRepository = $stockFeeRepository;
    }

    public function getAmount(Stock $stock): float {

        $amountFee = (float) $this->stockFeeRepository->getAmountByStock($stock);
        $amountProductStocks = (float) $this->productStockRepository
            ->getAmountByStock($stock);

        return $amountProductStocks + $amountFee;
    }

    /**
     * @param Stock[] $stocks
     * @return array
     */
    public function getAmounts($stocks): array {

        $stockAmounts = $this->productStockRepository->getAmountByStocks();
        $stockFeeAmounts = $this->stockFeeRepository->getAmountByStocks();

        $stockTabs = [];
        foreach ($stocks as $stock){
            $amount = 0;
            foreach ($stockAmounts as $stockAmount){
                if ((int) $stockAmount['id'] === $stock->getId()){
                    $amount += (float) $stockAmount['amount'];
                    break;
                }
            }

            foreach ($stockFeeAmounts as $stockFeeAmount){
                if ((int) $stockFeeAmount['id'] === $stock->getId()){
                    $amount += (float) $stockFeeAmount['amount'];
                    break;
                }
            }

            $stock->setAmount($amount);
            $stockTabs[] = $stock;
        }

        return $stockTabs;
    }

}
