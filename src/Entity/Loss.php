<?php

namespace App\Entity;

use App\Repository\LossRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=LossRepository::class)
 */
class Loss
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Positive()
     */
    private $qty=1;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @ORM\ManyToOne(targetEntity=LossType::class, inversedBy="losses",
     *     fetch="EAGER")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="losses",
     *     fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recorder;

    /**
     * @ORM\ManyToOne(targetEntity=ProductStock::class, inversedBy="losses",
     *     fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $productStock;

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

    public function getAddDate(): ?DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->getProductStock()->getProduct();
    }

    public function getType(): ?LossType
    {
        return $this->type;
    }

    public function setType(?LossType $type): self
    {
        $this->type = $type;

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

    public function getProductStock(): ?ProductStock
    {
        return $this->productStock;
    }

    public function setProductStock(?ProductStock $productStock): self
    {
        $this->productStock = $productStock;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->getQty() * $this->getProductStock()->getUnitPrice();
    }
}
