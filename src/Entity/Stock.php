<?php

namespace App\Entity;

use App\Repository\StockRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=StockRepository::class)
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"stock:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $amount = 0.0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @ORM\OneToMany(targetEntity=ProductStock::class, mappedBy="stock")
     */
    private $productStocks;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="stocks",
     *      fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recorder;

    /**
     *
     * @ORM\ManyToOne(targetEntity=Supplier::class, inversedBy="stocks",
     *     fetch="EAGER")
     * @Groups({"stock:read"})
     */
    private $supplier;

    /**
     * @ORM\Column(type="boolean")
     */
    private $status = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"stock:read"})
     */
    private $deliveryDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numInvoice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numBill;

    /**
     * @ORM\OneToMany(targetEntity=StockPayment::class, mappedBy="stock", orphanRemoval=true)
     */
    private $stockPayments;

    /**
     * @ORM\ManyToMany(targetEntity=Tax::class)
     */
    private $taxs;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $taxAmount = 0.0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $initial = false;

    /**
     * @ORM\Column(type="float")
     */
    private $amountSended = 0.0;

    /**
     * @ORM\OneToMany(targetEntity=StockFee::class, mappedBy="stock")
     */
    private $stockFees;

    public function __construct()
    {
        $this->productStocks = new ArrayCollection();
        $this->stockPayments = new ArrayCollection();
        $this->taxs = new ArrayCollection();
        $this->stockFees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isSettled(): ?bool
    {
        return $this->getAmountRemaining() <= 0;
    }

    public function getAmountSettled(): ?float
    {
        if (empty($this->getStockPayments()->toArray()))
            return 0;

        return array_sum(array_map(static function(StockPayment $stockPayment){
            return $stockPayment->getAmount();
        },$this->getStockPayments()->toArray()));
    }

    public function getAmountProductStocks(): ?float
    {
        if (empty($this->getProductStocks()->toArray()))
            return 0;

        return array_sum(array_map(static function(ProductStock $productStock){
            return $productStock->getSubtotal();
        },$this->getProductStocks()->toArray()));
    }

    public function getAmountDebt(): ?float
    {
        return $this->getTotal() - $this->getAmountTotalSended();
    }

    public function getAmountTotalSended(): ?float
    {
        return ($this->amountSended >= $this->getAmount())?
            $this->amountSended :
            $this->amountSended + $this->getAmountSettled();
    }

    public function getAmountSold(): ?float
    {
        if (empty($this->getProductStocks()->toArray()))
            return 0;

        return array_sum(array_map(static function(ProductStock $productStock){
            return $productStock->getAmountSold();
        },$this->getProductStocks()->toArray()));
    }

    public function getAmountRemaining(): ?float
    {
        return $this->getAmount() - $this->getAmountSettled();
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getAmountWithoutTax(): ?float
    {
        return $this->getAmount() - $this->getTaxAmount();
    }


    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAddDate(): ?DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

        return $this;
    }

    /**
     * @return Collection|ProductStock[]
     */
    public function getProductStocks(): Collection
    {
        return $this->productStocks
            ->filter(function (ProductStock $productStock){
                return $productStock->getQty() > 0;
            });
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDeliveryDate(): ?DateTimeInterface
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?DateTimeInterface $deliveryDate): self
    {
        $this->deliveryDate = $deliveryDate;

        return $this;
    }

    public function getNumInvoice(): ?string
    {
        return $this->numInvoice;
    }

    public function setNumInvoice(?string $numInvoice): self
    {
        $this->numInvoice = $numInvoice;

        return $this;
    }

    public function getNumBill(): ?string
    {
        return $this->numBill;
    }

    public function setNumBill(?string $numBill): self
    {
        $this->numBill = $numBill;

        return $this;
    }

    /**
     * @return Collection|StockPayment[]
     */
    public function getStockPayments(): Collection
    {
        return $this->stockPayments;
    }

    public function addStockPayment(StockPayment $stockPayment): self
    {
        if (!$this->stockPayments->contains($stockPayment)) {
            $this->stockPayments[] = $stockPayment;
            $stockPayment->setStock($this);
        }

        return $this;
    }

    public function removeStockPayment(StockPayment $stockPayment): self
    {
        if ($this->stockPayments->removeElement($stockPayment)) {
            // set the owning side to null (unless already changed)
            if ($stockPayment->getStock() === $this) {
                $stockPayment->setStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Tax[]
     */
    public function getTaxs(): Collection
    {
        return $this->taxs;
    }

    public function addTax(Tax $tax): self
    {
        if (!$this->taxs->contains($tax)) {
            $this->taxs[] = $tax;
        }

        return $this;
    }

    public function removeTax(Tax $tax): self
    {
        $this->taxs->removeElement($tax);

        return $this;
    }

    public function getTaxAmount(): ?float
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(?float $taxAmount): self
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function getInitial(): ?bool
    {
        return $this->initial;
    }

    public function setInitial(bool $initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    public function getAmountSended(): ?float
    {
        return $this->amountSended;
    }

    public function setAmountSended(float $amountSended): self
    {
        $this->amountSended = $amountSended;

        return $this;
    }

    /**
     * @return Collection<int, StockFee>
     */
    public function getStockFees(): Collection
    {
        return $this->stockFees;
    }

    public function addStockFee(StockFee $stockFee): self
    {
        if (!$this->stockFees->contains($stockFee)) {
            $this->stockFees[] = $stockFee;
            $stockFee->setStock($this);
        }

        return $this;
    }

    public function removeStockFee(StockFee $stockFee): self
    {
        if ($this->stockFees->removeElement($stockFee)) {
            // set the owning side to null (unless already changed)
            if ($stockFee->getStock() === $this) {
                $stockFee->setStock(null);
            }
        }

        return $this;
    }

    public function getTotalStocks(): ?float {
        return array_sum(array_map(
            static function(ProductStock $productionStock){
                return $productionStock->getSubTotal();
            },$this->getProductStocks()->toArray()));
    }

    public function getTotalFees(): ?float {
        return array_sum(array_map(
            static function(StockFee $stockFee){
                return $stockFee->getAmount();
            },$this->getStockFees()->toArray()));
    }

    public function getTotal(): ?float {
        return $this->getTotalStocks() - $this->getTotalFees();
    }

}
