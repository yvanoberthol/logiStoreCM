<?php


namespace App\Entity;

use App\Repository\SubscriptionRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass=SubscriptionRepository::class)
 */
class Subscription
{

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Package::class, inversedBy="subscriptions",
     *     fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @var Package
     */
    private $package;


    /**
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $date;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    private $created_at;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * @return Package
     */
    public function getPackage(): ?Package
    {
        return $this->package;
    }

    /**
     * @param Package $package
     * @return Subscription
     */
    public function setPackage(Package $package): self
    {
        $this->package = $package;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     * @return Subscription
     */
    public function setDate(DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->created_at;
    }

    /**
     * @param DateTime $created_at
     * @return Subscription
     */
    public function setCreatedAt(DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * @return DateTime|null
     * @throws Exception
     */
    public function getExpiration(): ?DateTime
    {
        $timestampDayExpiration = $this->package->getNbDays() * 24 * 3600;
        $dateTimeStamp = $this->date->getTimeStamp();

        $expirationTimeStamp = $timestampDayExpiration + $dateTimeStamp;

        return new DateTime(date('Y-m-d', $expirationTimeStamp));
    }

    /**
     * @return int|null
     * @throws Exception
     */
    public function getNbDayRemaining(): ?int
    {
        $today = new DateTime();
        $dateToday = date_create($today->format('Y-m-d'));
        $expirationDate = date_create($this->getExpiration()->format('Y-m-d'));

        return $this->expirationisUpThanToday()? $expirationDate->diff($dateToday)->days: 0;
    }

    /**
     * @return bool|null
     * @throws Exception
     */
    public function expirationisUpThanToday(): ?bool
    {
        return $this->getExpiration() > new DateTime();
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
