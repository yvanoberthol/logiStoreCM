<?php


namespace App\Entity;


class ProductImport
{
    /**
     * @var string
     */
    public $name = '';

    /**
     * @var float
     */
    public $buyPrice = 0.0;

    /**
     * @var float
     */
    public $sellPrice = 0.0;

    /**
     * @var integer
     */
    public $stockAlert = 0;

    /**
     * @var string
     */
    public $barcode = '';

    /**
     * @var string
     */
    public $reference = '';

    /**
     * @var string
     */
    public $category = '';

    /**
     * @var integer
     */
    public $stock = 0;

    /**
     * @var integer
     */
    public $packagingQty = 0;
}
