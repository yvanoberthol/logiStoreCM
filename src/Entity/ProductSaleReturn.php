<?php

namespace App\Entity;

use App\Repository\ProductSaleReturnRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductSaleReturnRepository::class)
 */
class ProductSaleReturn
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="integer")
     */
    private $qty;

    /**
     * @ORM\ManyToOne(targetEntity=ProductStockSale::class, inversedBy="productSaleReturns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productStockSale;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="productSaleReturns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recorder;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reason;

    /**
     * @ORM\Column(type="boolean")
     */
    private $repay=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $stockable=false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
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

    public function getProductStockSale(): ?ProductStockSale
    {
        return $this->productStockSale;
    }

    public function setProductStockSale(?ProductStockSale $productStockSale): self
    {
        $this->productStockSale = $productStockSale;

        return $this;
    }

    public function getRecorder(): ?User
    {
        return $this->recorder;
    }

    public function setRecorder(?User $recorder): self
    {
        $this->recorder = $recorder;

        return $this;
    }

    public function getSubtotal(): ?float
    {
        return $this->getQty() * $this->getUnitPrice();
    }

    public function getUnitPrice(): ?float
    {
        return ($this->getProductStockSale() !== null)
            ? $this->getProductStockSale()
                ->getProductSale()->getUnitPrice() :0;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getRepay(): ?bool
    {
        return $this->repay;
    }

    public function setRepay(bool $repay): self
    {
        $this->repay = $repay;

        return $this;
    }

    public function getStockable(): ?bool
    {
        return $this->stockable;
    }

    public function setStockable(bool $stockable): self
    {
        $this->stockable = $stockable;

        return $this;
    }
}
