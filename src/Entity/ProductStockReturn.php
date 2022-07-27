<?php

namespace App\Entity;

use App\Repository\ProductStockReturnRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductStockReturnRepository::class)
 */
class ProductStockReturn
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
     * @ORM\ManyToOne(targetEntity=ProductStock::class, inversedBy="productStockReturns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productStock;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="productStockReturns")
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

    public function getProductStock(): ?ProductStock
    {
        return $this->productStock;
    }

    public function setProductStock(?ProductStock $productStock): self
    {
        $this->productStock = $productStock;

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
        return ($this->getProductStock() !== null)
            ? $this->getProductStock()->getUnitPrice() :0;
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
}
