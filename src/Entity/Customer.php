<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Vich\Uploadable()
 * @ORM\Entity(repositoryClass=CustomerRepository::class)
 */
class Customer
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $other;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $addDate;

    /**
     * @ORM\OneToMany(targetEntity=Sale::class, mappedBy="customer")
     */
    private $sales;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private $type = 'S';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imageName;

    /**
     * @Assert\Image()
     * @Vich\UploadableField(fileNameProperty="imageName",mapping="image_user")
     * @var File|null
     */
    protected $imageFile;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity=CustomerProductPrice::class, mappedBy="customer", orphanRemoval=true)
     */
    private $customerProductPrices;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
        $this->customerProductPrices = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getOther(): ?string
    {
        return $this->other;
    }

    public function setOther(?string $other): self
    {
        $this->other = $other;

        return $this;
    }

    public function getAddDate(): ?DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(?DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

        return $this;
    }

    /**
     * @return Collection|Sale[]
     */
    public function getSales(): Collection
    {
        return $this->sales->filter(static function(Sale $sale){
            return !$sale->getDeleted();
        });
    }

    public function getSaleNotSettled(): array
    {
        return array_filter($this->getSales()->toArray(),static function(Sale $sale){
            return $sale->getAmountDebt() > 0.0;
        });
    }

    public function getPoints(): ?int
    {
        if (empty($this->getSales()->toArray()))
            return 0;

        return array_sum(array_map(static function(Sale $sale){
            return $sale->getPoints();
        },$this->getSales()->toArray()));
    }


    public function getAmountDebt(): ?float
    {
        if (empty($this->getSales()->toArray()))
            return 0;

        return array_sum(array_map(static function(Sale $sale){
            return $sale->getAmountDebt();
        },$this->getSales()->toArray()));
    }

    public function addSale(Sale $sale): self
    {
        if (!$this->sales->contains($sale)) {
            $this->sales[] = $sale;
            $sale->setCustomer($this);
        }

        return $this;
    }

    public function removeSale(Sale $sale): self
    {
        // set the owning side to null (unless already changed)
        if ($this->sales->removeElement($sale)
            && $sale->getCustomer() === $this) {
            $sale->setCustomer(null);
        }

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

    public function getInitials(): ?string
    {
        return substr($this->name,0,1);
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @param File|null $imageFile
     * @return Customer
     * @throws Exception
     */
    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile){
            $this->updatedAt = new DateTimeImmutable();
        }
        return $this;
    }

    /**
     * @return Collection|CustomerProductPrice[]
     */
    public function getCustomerProductPrices(): Collection
    {
        return $this->customerProductPrices;
    }

    public function addCustomerProductPrice(CustomerProductPrice $customerProductPrice): self
    {
        if (!$this->customerProductPrices->contains($customerProductPrice)) {
            $this->customerProductPrices[] = $customerProductPrice;
            $customerProductPrice->setProduct($this);
        }

        return $this;
    }

    public function removeCustomerProductPrice(CustomerProductPrice $customerProductPrice): self
    {
        if ($this->customerProductPrices->removeElement($customerProductPrice)) {
            // set the owning side to null (unless already changed)
            if ($customerProductPrice->getProduct() === $this) {
                $customerProductPrice->setProduct(null);
            }
        }

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
