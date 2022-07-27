<?php


namespace App\Entity;


class ProductUpdateImport
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
}
