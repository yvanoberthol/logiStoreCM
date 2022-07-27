<?php

namespace App\Entity;

use App\Repository\EncashmentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EncashmentRepository::class)
 */
class Encashment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="float")
     */
    private $initialBalance = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $amountReceived = 0.0;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="encashments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $employee;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $recorderName;

    private $totalCredits = 0.0;
    private $totalAmountSold = 0.0;
    private $totalToDeposit = 0.0;
    private $totalGap = 0.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getInitialBalance(): ?float
    {
        return $this->initialBalance;
    }

    public function setInitialBalance(float $initialBalance): self
    {
        $this->initialBalance = $initialBalance;

        return $this;
    }

    public function getAmountReceived(): ?float
    {
        return $this->amountReceived;
    }

    public function setAmountReceived(float $amountReceived): self
    {
        $this->amountReceived = $amountReceived;

        return $this;
    }

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function setEmployee(?User $employee): self
    {
        $this->employee = $employee;

        return $this;
    }

    public function getRecorderName(): ?string
    {
        return $this->recorderName;
    }

    public function setRecorderName(string $recorderName): self
    {
        $this->recorderName = $recorderName;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalCredits(): ?float
    {
        return $this->totalCredits;
    }

    /**
     * @param float $totalCredits
     * @return Encashment
     */
    public function setTotalCredits(float $totalCredits): self
    {
        $this->totalCredits = $totalCredits;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalAmountSold(): ?float
    {
        return $this->totalAmountSold;
    }

    /**
     * @param float $totalAmountSold
     * @return Encashment
     */
    public function setTotalAmountSold(float $totalAmountSold): self
    {
        $this->totalAmountSold = $totalAmountSold;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalToDeposit(): ?float
    {
        return $this->totalToDeposit;
    }

    /**
     * @param float $totalToDeposit
     * @return Encashment
     */
    public function setTotalToDeposit(float $totalToDeposit): self
    {
        $this->totalToDeposit = $totalToDeposit;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalGap(): ?float
    {
        return $this->totalGap;
    }

    /**
     * @param float $totalGap
     * @return Encashment
     */
    public function setTotalGap(float $totalGap): self
    {
        $this->totalGap = $totalGap;
        return $this;
    }
}
