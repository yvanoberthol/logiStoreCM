<?php

namespace App\Entity;

use App\Repository\ProductStockRepository;
use App\Util\GlobalConstant;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass=ProductStockRepository::class)
 */
class ProductStock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"productStock:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $qty=0;

    /**
     * @ORM\Column(type="float")
     */
    private $unitPrice=0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $subtotal=0.0;

    /**
     * @Groups(groups="productStock:read")
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="productStocks",
     *     fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @Groups(groups="productStock:read")
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="productStocks",
     *     fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $stock;

    /**
     * @ORM\OneToMany(targetEntity=Loss::class, mappedBy="productStock", orphanRemoval=true)
     */
    private $losses;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $expirationDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withdraw = false;

    /**
     * @ORM\OneToMany(targetEntity=ProductStockSale::class, mappedBy="productStock")
     */
    private $productStockSales;


    private $qtySold;
    private $qtyLost;
    private $qtySoldReturn;
    private $qtyStockReturn;
    private $qtyRemaining;

    /**
     * @ORM\OneToMany(targetEntity=ProductStockReturn::class,
     *     mappedBy="productStock", orphanRemoval=true)
     */
    private $productStockReturns;

    /**
     * @ORM\OneToMany(targetEntity=ProductAdjustStock::class, mappedBy="productStock", orphanRemoval=true)
     */
    private $productAdjustStocks;

    public function __construct()
    {
        $this->losses = new ArrayCollection();
        $this->productStockSales = new ArrayCollection();
        $this->productStockReturns = new ArrayCollection();
        $this->productAdjustStocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"productStock:read"})
     */
    public function getBatchId(): ?string
    {
        return  $this->getStockId().'-'.str_pad($this->getId(),4,'0',STR_PAD_LEFT);
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

    public function getStockId(): ?int
    {
        if ($this->getStock() !== null)
            return $this->getStock()->getId();

        return 0;
    }

    public function getStockDate(): ?DateTimeInterface
    {
        if ($this->getStock() !== null)
            return $this->getStock()->getDeliveryDate();

        return null;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @Groups({"productStock:read"})
     * @return int
     */
    public function getQtyRemaining(): ?int
    {
        return $this->qtyRemaining;

    }

    /**
     * @param int $qtyRemaining
     * @return ProductStock
     */
    public function setQtyRemaining(int $qtyRemaining): self
    {
        $this->qtyRemaining = $qtyRemaining;
        return $this;
    }

    /**
     * @return int
     */
    public function getQtySold(): ?int
    {
        return $this->qtySold;
    }

    /**
     * @param int $qtySold
     * @return ProductStock
     */
    public function setQtySold(?int $qtySold): self
    {
        $this->qtySold = $qtySold;
        return $this;
    }

    /**
     * @return int
     */
    public function getQtyLost(): ?int
    {
        return $this->qtyLost;
    }

    /**
     * @param int $qtyLost
     * @return ProductStock
     */
    public function setQtyLost(?int $qtyLost): self
    {
        $this->qtyLost = $qtyLost;
        return $this;
    }

    public function getAmountSold(): ?float {
        return array_sum(array_map(static function(ProductStockSale $productStockSale){
            return $productStockSale->getQty() * $productStockSale->getUnitPrice();
        },$this->getProductStockSales()->toArray()));
    }

    /**
     * @return int
     */
    public function getQtySoldReturn(): ?int
    {
        return $this->qtySoldReturn;
    }

    /**
     * @param int $qtySoldReturn
     * @return ProductStock
     */
    public function setQtySoldReturn(?int $qtySoldReturn): self
    {
        $this->qtySoldReturn = $qtySoldReturn;
        return $this;
    }

    /**
     * @return int
     */
    public function getQtyStockReturn(): ?int
    {
        return $this->qtyStockReturn;
    }

    /**
     * @param int $qtyStockReturn
     * @return ProductStock
     */
    public function setQtyStockReturn(?int $qtyStockReturn): self
    {
        $this->qtyStockReturn = $qtyStockReturn;
        return $this;
    }


    public function getQtyOutOfDate(): ?int{
        if ($this->getLosses()->count() > 0){
            $losses = $this->getLosses()
                ->filter(static function(Loss $loss){
                    return strtolower($loss->getType()->getName()) === strtolower(GlobalConstant::OUTOFDATE);
                })->map(static function(Loss $loss){
                    return $loss->getQty();
                })->toArray();

            return array_sum($losses);
        }

        return 0;
    }

    public function getQtyLosses(): ?int{
        return array_sum($this->getLosses()->map(static function(Loss $loss){
            return $loss->getQty();
        })->toArray());
    }

    public function getQtyStockReturns(): ?int{
        return array_sum($this->getProductStockReturns()
            ->map(static function(ProductStockReturn $productStockReturn){
            return $productStockReturn->getQty();
        })->toArray());
    }

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
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
     * @return ProductStock
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
    }

    /**
     * @return Collection|Loss[]
     */
    public function getLosses(): Collection
    {
        return $this->losses;
    }

    public function getExpirationDate(): ?DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function getOutOfDate(): bool {
        if ($this->getInterval() === null){
            return false;
        }
        return $this->getInterval() <= 0;
    }

    /**
     * @return int|null
     * @throws Exception
     */
    public function getInterval(): ?int {
        if ($this->getExpirationDate() !== null){
            return GlobalConstant
                ::getInterval(new DateTime(), $this->getExpirationDate());
        }

        return null;

    }

    public function getWithdraw(): ?bool
    {
        return $this->withdraw;
    }

    public function setWithdraw(bool $withdraw): self
    {
        $this->withdraw = $withdraw;

        return $this;
    }

    /**
     * @return Collection|ProductStockSale[]
     */
    public function getProductStockSales(): Collection
    {
        return $this->productStockSales;
    }

    public function addProductStockSale(ProductStockSale $productStockSale): self
    {
        if (!$this->productStockSales->contains($productStockSale)) {
            $this->productStockSales[] = $productStockSale;
            $productStockSale->setProductStock($this);
        }

        return $this;
    }

    public function removeProductStockSale(ProductStockSale $productStockSale): self
    {
        if ($this->productStockSales->removeElement($productStockSale)) {
            // set the owning side to null (unless already changed)
            if ($productStockSale->getProductStock() === $this) {
                $productStockSale->setProductStock(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|ProductStockReturn[]
     */
    public function getProductStockReturns(): Collection
    {
        return $this->productStockReturns;
    }

    public function addProductStockReturn(ProductStockReturn $productStockReturn): self
    {
        if (!$this->productStockReturns->contains($productStockReturn)) {
            $this->productStockReturns[] = $productStockReturn;
            $productStockReturn->setProductStock($this);
        }

        return $this;
    }

    public function removeProductStockReturn(ProductStockReturn $productStockReturn): self
    {
        if ($this->productStockReturns->removeElement($productStockReturn)) {
            // set the owning side to null (unless already changed)
            if ($productStockReturn->getProductStock() === $this) {
                $productStockReturn->setProductStock(null);
            }
        }

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
            $productAdjustStock->setProductStock($this);
        }

        return $this;
    }

    public function removeProductAdjustStock(ProductAdjustStock $productAdjustStock): self
    {
        if ($this->productAdjustStocks->removeElement($productAdjustStock)) {
            // set the owning side to null (unless already changed)
            if ($productAdjustStock->getProductStock() === $this) {
                $productAdjustStock->setProductStock(null);
            }
        }

        return $this;
    }
}
