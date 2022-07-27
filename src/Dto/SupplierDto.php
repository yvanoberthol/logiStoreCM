<?php


namespace App\Dto;


use App\Entity\Supplier;

class SupplierDto
{
    public $id;
    public $name;

    public static function createFromEntity(Supplier $entity): self {
        $supplier = new self();
        $supplier->id = $entity->getId();
        $supplier->name = $entity->getName();

        return $supplier;
    }


}
