<?php

namespace App\Entity;

use App\Repository\ProductSaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass=ProductSaleRepository::class)
 */
class ProductSale
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
     * @ORM\Column(type="float")
     */
    private $subtotal;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class,
     *     inversedBy="productSales",fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Sale::class,
     *     inversedBy="productSales", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sale;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $profit;

    /**
     * @ORM\OneToMany(targetEntity=ProductStockSale::class, mappedBy="productSale")
     */
    private $productStockSales;

    /**
     * @ORM\Column(type="integer")
     */
    private $point = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $discount = 0.0;

    public function __construct()
    {
        $this->productStockSales = new ArrayCollection();
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): self
    {
        $this->sale = $sale;

        return $this;
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): self
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    /**
     * @param float $unitPrice
     * @return ProductSale
     */
    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate()
     */
    public function set(): void {
        $this->subtotal = $this->getQty() * $this->getUnitPrice();

        $this->profit = array_sum(array_map(
                static function(ProductStockSale $productStockSale){
                    return $productStockSale->getProfit();
                },$this->getProductStockSales()->toArray())) - $this->getDiscount();
    }

    public function getProfit(): ?int
    {
        return $this->profit;
    }

    public function setProfit(?int $profit): self
    {
        $this->profit = $profit;

        return $this;
    }

    /**
     * @return Collection|ProductStockSale[]
     */
    public function getProductStockSales(): Collection
    {
        return $this->productStockSales;
    }

    public function getQtySold(){
        return array_sum(array_map(
            static function(ProductStockSale $productStockSale){
            return $productStockSale->getQty();
        },$this->getProductStockSales()->toArray()));
    }

    public function getQtyRemaining(){
        return array_sum(array_map(
            static function(ProductStockSale $productStockSale){
                return $productStockSale->getQtyRemaining();
            },$this->getProductStockSales()->toArray()));
    }

    public function addProductStockSale(ProductStockSale $productStockSale): self
    {
        if (!$this->productStockSales->contains($productStockSale)) {
            $this->productStockSales[] = $productStockSale;
            $productStockSale->setProductSale($this);
        }

        return $this;
    }

    public function removeProductStockSale(ProductStockSale $productStockSale): self
    {
        if ($this->productStockSales->removeElement($productStockSale)) {
            // set the owning side to null (unless already changed)
            if ($productStockSale->getProductSale() === $this) {
                $productStockSale->setProductSale(null);
            }
        }

        return $this;
    }

    public function getPoint(): ?int
    {
        return $this->point;
    }

    public function setPoint(int $point): self
    {
        $this->point = $point;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;

        return $this;
    }
}
