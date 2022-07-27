<?php

namespace App\Entity;

use App\Repository\ProductAdjustRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductAdjustRepository::class)
 */
class ProductAdjust
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
    private $qty = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $unitPrice;

    /**
     * @ORM\OneToMany(targetEntity=ProductAdjustStock::class, mappedBy="productAdjust", orphanRemoval=true)
     */
    private $productAdjustStocks;

    /**
     * @ORM\ManyToOne(targetEntity=Adjustment::class, inversedBy="productAdjusts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $adjustment;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="productAdjusts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\Column(type="integer")
     */
    private $qtyBeforeAdjust = 0.0;

    /**
     * @ORM\Column(type="integer")
     */
    private $newQty = 0.0;

    public function __construct()
    {
        $this->productAdjustStocks = new ArrayCollection();
    }

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

    public function getSubtotal(): ?float
    {
        return $this->getUnitPrice() * $this->getQty();
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * @return Collection|ProductAdjustStock[]
     */
    public function getProductAdjustStocks(): Collection
    {
        return $this->productAdjustStocks;
    }

    public function addProductAdjustStock(ProductAdjustStock $productAdjustStock): self
    {
        if (!$this->productAdjustStocks->contains($productAdjustStock)) {
            $this->productAdjustStocks[] = $productAdjustStock;
            $productAdjustStock->setProductAdjust($this);
        }

        return $this;
    }

    public function removeProductAdjustStock(ProductAdjustStock $productAdjustStock): self
    {
        if ($this->productAdjustStocks->removeElement($productAdjustStock)) {
            // set the owning side to null (unless already changed)
            if ($productAdjustStock->getProductAdjust() === $this) {
                $productAdjustStock->setProductAdjust(null);
            }
        }

        return $this;
    }

    public function getAdjustment(): ?Adjustment
    {
        return $this->adjustment;
    }

    public function setAdjustment(?Adjustment $adjustment): self
    {
        $this->adjustment = $adjustment;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQtyBeforeAdjust(): ?int
    {
        return $this->qtyBeforeAdjust;
    }

    public function setQtyBeforeAdjust(int $qtyBeforeAdjust): self
    {
        $this->qtyBeforeAdjust = $qtyBeforeAdjust;

        return $this;
    }

    public function getNewQty(): ?int
    {
        return $this->newQty;
    }

    public function setNewQty(int $newQty): self
    {
        $this->newQty = $newQty;

        return $this;
    }
}
