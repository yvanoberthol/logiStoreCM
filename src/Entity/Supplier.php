<?php

namespace App\Entity;

use App\Repository\SupplierRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields={"name"},
 *     message="entity.supplier.name"
 * )
 * @ORM\Entity(repositoryClass=SupplierRepository::class)
 */
class Supplier
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups({"supplier:read"})
     * @ORM\Column(type="string", length=255,unique=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $firstPhoneNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $secondPhoneNumber;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity=Stock::class, mappedBy="supplier")
     */
    private $stocks;


    public function __construct()
    {
        $this->stocks = new ArrayCollection();
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

    public function getInitials(): ?string
    {
        return substr($this->name,0,1);
    }

    public function getFirstPhoneNumber(): ?string
    {
        return $this->firstPhoneNumber;
    }

    public function setFirstPhoneNumber(?string $firstPhoneNumber): self
    {
        $this->firstPhoneNumber = $firstPhoneNumber;

        return $this;
    }

    public function getSecondPhoneNumber(): ?string
    {
        return $this->secondPhoneNumber;
    }

    public function setSecondPhoneNumber(?string $secondPhoneNumber): self
    {
        $this->secondPhoneNumber = $secondPhoneNumber;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDate(): void {
        $this->addDate = new DateTime();
    }

    public function getAmountDebt(): ?float
    {
        if (empty($this->getStocks()->toArray()))
            return 0.0;

        return array_sum(array_map(static function(Stock $stock){
            return $stock->getAmountDebt();
        },$this->getStocks()->toArray()));
    }


    /**
     * @return Collection|Stock[]
     */
    public function getStocks(): Collection
    {
        return $this->stocks->filter(static function(Stock $stock){
            return $stock->getStatus();
        });
    }

    /**
     * @return Stock[]
     */
    public function getStockNotSettled(): array
    {
        return array_filter($this->getStocks()->toArray(),static function(Stock $stock){
            return $stock->getAmountDebt() > 0;
        });
    }
}
