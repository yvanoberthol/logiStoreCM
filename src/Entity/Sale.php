<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass=SaleRepository::class)
 */
class Sale
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $amount = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $profit = 0.0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @ORM\OneToMany(targetEntity=ProductSale::class, mappedBy="sale",
     *     fetch="EXTRA_LAZY")
     */
    private $productSales;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="sales",
     *     fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recorder;

    /**
     * @ORM\ManyToOne(targetEntity=PaymentMethod::class, inversedBy="sales",fetch="EAGER")
     */
    private $paymentMethod;

    /**
     * @ORM\Column(type="boolean")
     */
    private $deleted = false;

    /**
     * @ORM\Column(type="float")
     */
    private $amountReceived = 0.0;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="sales")
     */
    private $customer;

    /**
     * @ORM\ManyToMany(targetEntity=Tax::class)
     */
    private $taxs;

    /**
     * @ORM\Column(type="float")
     */
    private $taxAmount = 0;

    /**
     * @ORM\OneToMany(targetEntity=SalePayment::class, mappedBy="sale", orphanRemoval=true)
     */
    private $salePayments;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $reason;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="float")
     */
    private $discount = 0.0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDraft = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $adjusted=false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $numInvoice;


    public function __construct()
    {
        $this->productSales = new ArrayCollection();
        $this->taxs = new ArrayCollection();
        $this->salePayments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(float $profit): self
    {
        $this->profit = $profit;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getAmountWithoutDiscount(): ?float
    {
        return $this->amount + $this->getDiscount();
    }


    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getBarCode(): ?string
    {
        if ($this->getCode() === null){
            return null;
        }
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($this->getCode(), $generator::TYPE_CODE_128,
            1, 20, [0, 0, 0]);
        return base64_encode($barcode);
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
     * @return Collection|ProductSale[]
     */
    public function getProductSales(): Collection
    {
        return $this->productSales;
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

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getAmountReceived(): ?float
    {
        return $this->amountReceived;
    }

    public function getAmountTotalReceived($start=null,$end=null): ?float
    {

        return ($this->amountReceived >= $this->getAmount())?
            $this->amountReceived - $this->getAmountToRepay():
            $this->amountReceived + $this->getAmountSettled($start,$end);
    }

    public function getTaxAmount(): ?float
    {
        return $this->taxAmount;
    }

    public function getAmountWithoutTax(): ?float
    {
        return $this->getAmount() - $this->getTaxAmount();
    }

    public function getAmountToRepay(): ?float
    {
        return $this->getAmountReceived() - $this->getAmount();
    }

    public function getAmountDebt(): ?float
    {
        return $this->getAmount() - $this->getAmountTotalReceived();
    }

    public function getAmountSettled($start=null,$end=null): ?float
    {
        if (empty($this->getSalePayments()->toArray()))
            return 0;

        $salePayments = ($start!==null && $end!==null)?array_filter($this->getSalePayments()->toArray(),
            static function(SalePayment $salePayment) use ($start,$end){
                return ($salePayment->getAddDate() !== null &&
                    $salePayment->getAddDate() >= $start &&
                    $salePayment->getAddDate() <= $end);
            }): $this->getSalePayments()->toArray();

        return array_sum(array_map(static function(SalePayment $salePayment){
            return $salePayment->getAmount();
        },$salePayments));
    }

    public function getAmountProductSales(): ?float
    {
        if (empty($this->getProductSales()->toArray()))
            return 0;

        return array_sum(array_map(static function(ProductSale $productSale) {
            return $productSale->getSubtotal();
        },$this->getProductSales()->toArray()));
    }

    public function getPoints(): ?int
    {
        if (empty($this->getProductSales()->toArray()))
            return 0;

        return array_sum(array_map(static function(ProductSale $productSale) {
            return $productSale->getPoint();
        },$this->getProductSales()->toArray()));
    }

    public function getProfitProductSales(): ?float
    {
        if (empty($this->getProductSales()->toArray()))
            return 0;

        return array_sum(array_map(static function(ProductSale $productSale) {
            return $productSale->getProfit();
        },$this->getProductSales()->toArray()));
    }

    public function setAmountReceived(float $amountReceived): self
    {
        $this->amountReceived = $amountReceived;

        return $this;
    }

    public function getCode(): ?string {
        return str_pad($this->getId(),6,'0',STR_PAD_LEFT);
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

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

    public function setTaxAmount(float $taxAmount): self
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * @return Collection|SalePayment[]
     */
    public function getSalePayments(): Collection
    {
        return $this->salePayments;
    }




    public function addSalePayment(SalePayment $salePayment): self
    {
        if (!$this->salePayments->contains($salePayment)) {
            $this->salePayments[] = $salePayment;
            $salePayment->setSale($this);
        }

        return $this;
    }

    public function removeSalePayment(SalePayment $salePayment): self
    {
        if ($this->salePayments->removeElement($salePayment)) {
            // set the owning side to null (unless already changed)
            if ($salePayment->getSale() === $this) {
                $salePayment->setSale(null);
            }
        }

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function getIsDraft(): ?bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): self
    {
        $this->isDraft = $isDraft;

        return $this;
    }

    public function getAdjusted(): ?bool
    {
        return $this->adjusted;
    }

    public function setAdjusted(bool $adjusted): self
    {
        $this->adjusted = $adjusted;

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
}
