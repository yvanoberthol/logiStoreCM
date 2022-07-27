<?php


namespace App\Dto;


use App\Entity\Product;

class ProductDto
{
    public $id;
    public $reference;
    public $name;
    public $qrCode;
    public $sellPrice;
    public $buyPrice;
    public $stock;
    public $initialStock;
    public $stockAlert;
    public $packagingQty;

    public static function createFromEntity(Product $entity): self {
        $product = new self();
        $product->id = $entity->getId();
        $product->reference = $entity->getReference();
        $product->qrCode = $entity->getQrCode();
        $product->name = $entity->getName();
        $product->buyPrice = $entity->getBuyPrice().'';
        $product->sellPrice = $entity->getSellPrice().'';
        $product->stock = $entity->getStock().'';
        $product->stockAlert = $entity->getStockAlert().'';
        $product->packagingQty = $entity->getPackagingQty().'';

        return $product;
    }


}
