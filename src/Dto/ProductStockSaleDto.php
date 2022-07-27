<?php


namespace App\Dto;


use App\Entity\ProductStockSale;

class ProductStockSaleDto
{
    public $id;
    public $qty;
    public $qtyRemaining;
    public $profit;
    public $productStock;

    public static function createFromEntity(ProductStockSale $entity,$withProductStock=false): self {
        $productStock = new self();

        $productStock->id = $entity->getId();
        $productStock->qty = $entity->getQty();
        $productStock->qtyRemaining = $entity->getQtyRemaining();
        $productStock->profit = $entity->getProfit();

        if ($withProductStock && $entity->getProductStock() !== null){
            $productStock->productStock = ProductStockDto
                ::createFromEntity($entity->getProductStock(),true,true);
        }

        return $productStock;
    }

}
