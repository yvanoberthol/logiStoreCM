<?php

namespace App\Entity;

use App\Repository\ProductAdjustStockRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductAdjustStockRepository::class)
 */
class ProductAdjustStock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $qty;

    /**
     * @ORM\Column(type="float")
     */
    private $unitPrice;

    /**
     * @ORM\ManyToOne(targetEntity=ProductStock::class, inversedBy="productAdjustStocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productStock;

    /**
     * @ORM\ManyToOne(targetEntity=ProductAdjust::class, inversedBy="productAdjustStocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productAdjust;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getProductStock(): ?ProductStock
    {
        return $this->productStock;
    }

    public function setProductStock(?ProductStock $productStock): self
    {
        $this->productStock = $productStock;

        return $this;
    }

    public function getProductAdjust(): ?ProductAdjust
    {
        return $this->productAdjust;
    }

    public function setProductAdjust(?ProductAdjust $productAdjust): self
    {
        $this->productAdjust = $productAdjust;

        return $this;
    }
}
