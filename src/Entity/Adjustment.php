<?php

namespace App\Entity;

use App\Repository\AdjustmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AdjustmentRepository::class)
 */
class Adjustment
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
    private $addDate;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="adjustments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recorder;

    /**
     * @ORM\OneToMany(targetEntity=ProductAdjust::class, mappedBy="adjustment", orphanRemoval=true)
     */
    private $productAdjusts;

    public function __construct()
    {
        $this->productAdjusts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string {
        return str_pad($this->getId(),6,'0',STR_PAD_LEFT);
    }

    public function getAddDate(): ?\DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(\DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

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

    public function getAmount(): ?float
    {
        if (empty($this->getProductAdjusts()->toArray()))
            return 0;

        return array_sum(array_map(static function(ProductAdjust $productAdjust) {
            return $productAdjust->getSubtotal();
        },$this->getProductAdjusts()->toArray()));
    }

    /**
     * @return Collection|ProductAdjust[]
     */
    public function getProductAdjusts(): Collection
    {
        return $this->productAdjusts;
    }

    public function addProductAdjust(ProductAdjust $productAdjust): self
    {
        if (!$this->productAdjusts->contains($productAdjust)) {
            $this->productAdjusts[] = $productAdjust;
            $productAdjust->setAdjustment($this);
        }

        return $this;
    }

    public function removeProductAdjust(ProductAdjust $productAdjust): self
    {
        if ($this->productAdjusts->removeElement($productAdjust)) {
            // set the owning side to null (unless already changed)
            if ($productAdjust->getAdjustment() === $this) {
                $productAdjust->setAdjustment(null);
            }
        }

        return $this;
    }
}
