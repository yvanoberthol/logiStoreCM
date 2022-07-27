<?php


namespace App\Dto;


use App\Entity\Product;
use App\Entity\ProductStock;

class ProductStockDto
{
    public $id;
    public $batchId;
    public $qty;
    public $qtySold=0;
    public $qtyLost=0;
    public $qtyRemaining=0;
    public $stock=0;
    public $product;

    public static function createFromEntity(ProductStock $entity,$withStock=false,$withProduct=false): self {
        $productStock = new self();

        if ($withProduct && $entity->getProduct() !== null){
            $productStock->product = ProductDto::createFromEntity($entity->getProduct());
        }

        if ($withStock && $entity->getStock() !== null){
            $productStock->stock = StockDto::createFromEntity($entity->getStock());
        }

        $productStock->id = $entity->getId();
        $productStock->batchId = $entity->getBatchId();
        $productStock->qty = $entity->getQty();
        $productStock->qtySold = $entity->getQtySold();
        $productStock->qtyLost = $entity->getQtyLost();
        $productStock->qtyRemaining = $entity->getQtyRemaining();

        return $productStock;
    }

}
