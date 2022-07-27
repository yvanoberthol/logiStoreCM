<?php


namespace App\Dto;


use App\Entity\Product;
use App\Entity\Stock;

class StockDto
{
    public $id;
    public $deliveryDate;
    public $supplier;

    public static function createFromEntity(Stock $entity): self {
        $stock = new self();
        $stock->id = $entity->getId();
        $stock->deliveryDate = $entity->getDeliveryDate();

        if ($entity->getSupplier() !== null){
            $stock->supplier = SupplierDto::createFromEntity($entity->getSupplier());
        }


        return $stock;
    }


}
