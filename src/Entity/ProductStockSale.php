<?php

namespace App\Entity;

use App\Repository\ProductStockSaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductStockSaleRepository::class)
 */
class ProductStockSale
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=ProductStock::class, inversedBy="productStockSales")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productStock;

    /**
     * @ORM\ManyToOne(targetEntity=ProductSale::class, inversedBy="productStockSales")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productSale;

    /**
     * @ORM\Column(type="integer")
     */
    private $qty;

    /**
     * @ORM\OneToMany(targetEntity=ProductSaleReturn::class, mappedBy="productStockSale", orphanRemoval=true)
     */
    private $productSaleReturns;

    /**
     * @ORM\Column(type="float")
     */
    private $unitPrice;

    public function __construct()
    {
        $this->productSaleReturns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProductSale(): ?ProductSale
    {
        return $this->productSale;
    }

    public function setProductSale(?ProductSale $productSale): self
    {
        $this->productSale = $productSale;

        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function getProfit(): ?int
    {
        return $this->getQty() *
            ($this->getProductSale()->getUnitPrice() -
                $this->getProductStock()->getUnitPrice());
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;

        return $this;
    }

    public function getQtyRemaining(){
        return $this->getQty() - $this->getQtyReturn();
    }

    public function getQtyReturn(){
        return array_sum(array_map(
            static function(ProductSaleReturn $productSaleReturn){
                return $productSaleReturn->getQty();
            },$this->getProductSaleReturns()->toArray()));
    }

    public function getQtyStockable(){
        return array_sum(array_map(
            static function(ProductSaleReturn $productSaleReturn){
                return ($productSaleReturn->getStockable())
                    ?$productSaleReturn->getQty() :0;
            },$this->getProductSaleReturns()->toArray()));
    }

    public function getAmountRepay(){
        return array_sum(array_map(
            static function(ProductSaleReturn $productSaleReturn){

                return ($productSaleReturn->getStockable())
                    ?$productSaleReturn->getQty() :0;
            },$this->getProductSaleReturns()->toArray()));
    }

    /**
     * @return Collection|ProductSaleReturn[]
     */
    public function getProductSaleReturns(): Collection
    {
        return $this->productSaleReturns;
    }

    public function addProductSaleReturn(ProductSaleReturn $productSaleReturn): self
    {
        if (!$this->productSaleReturns->contains($productSaleReturn)) {
            $this->productSaleReturns[] = $productSaleReturn;
            $productSaleReturn->setProductStockSale($this);
        }

        return $this;
    }

    public function removeProductSaleReturn(ProductSaleReturn $productSaleReturn): self
    {
        if ($this->productSaleReturns->removeElement($productSaleReturn)) {
            // set the owning side to null (unless already changed)
            if ($productSaleReturn->getProductStockSale() === $this) {
                $productSaleReturn->setProductStockSale(null);
            }
        }

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
}
