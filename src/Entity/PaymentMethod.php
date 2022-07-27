<?php

namespace App\Entity;

use App\Repository\PaymentMethodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentMethodRepository::class)
 */
class PaymentMethod
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255,unique=true)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Sale::class, mappedBy="paymentMethod")
     */
    private $sales;

    /**
     * @ORM\OneToMany(targetEntity=StockPayment::class, mappedBy="paymentMethod")
     */
    private $stockPayments;

    /**
     * @ORM\OneToMany(targetEntity=SalePayment::class, mappedBy="paymentMethod")
     */
    private $salePayments;

    /**
     * @ORM\OneToMany(targetEntity=Expense::class, mappedBy="paymentMethod")
     */
    private $expenses;

    /**
     * @ORM\Column(type="boolean")
     */
    private $status=true;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
        $this->stockPayments = new ArrayCollection();
        $this->salePayments = new ArrayCollection();
        $this->expenses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Sale[]
     */
    public function getSales(): Collection
    {
        return $this->sales;
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
            $stockPayment->setPaymentMethod($this);
        }

        return $this;
    }

    public function removeStockPayment(StockPayment $stockPayment): self
    {
        if ($this->stockPayments->removeElement($stockPayment)) {
            // set the owning side to null (unless already changed)
            if ($stockPayment->getPaymentMethod() === $this) {
                $stockPayment->setPaymentMethod(null);
            }
        }

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
            $salePayment->setPaymentMethod($this);
        }

        return $this;
    }

    public function removeSalePayment(SalePayment $salePayment): self
    {
        if ($this->salePayments->removeElement($salePayment)) {
            // set the owning side to null (unless already changed)
            if ($salePayment->getPaymentMethod() === $this) {
                $salePayment->setPaymentMethod(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Expense[]
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $expense): self
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses[] = $expense;
            $expense->setPaymentMethod($this);
        }

        return $this;
    }

    public function removeExpense(Expense $expense): self
    {
        if ($this->expenses->removeElement($expense)) {
            // set the owning side to null (unless already changed)
            if ($expense->getPaymentMethod() === $this) {
                $expense->setPaymentMethod(null);
            }
        }

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

}
