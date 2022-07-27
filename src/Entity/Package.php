<?php

namespace App\Entity;

use App\Repository\PackageRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PackageRepository::class)
 *
 */
class Package
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255,unique=true)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $nbDays;

    /**
     * @ORM\OneToMany(targetEntity=Subscription::class, mappedBy="package")
     */
    private $subscriptions;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection| Subscription[]
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }


    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Package
     */
    public function setName(string $name): Package
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getNbDays(): ?int
    {
        return $this->nbDays;
    }

    /**
     * @param int $nbDays
     * @return Package
     */
    public function setNbDays(int $nbDays): Package
    {
        $this->nbDays = $nbDays;
        return $this;
    }

}
