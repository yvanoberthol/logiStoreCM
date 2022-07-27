<?php

namespace App\Entity;

use App\Repository\NoticeBoardRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NoticeBoardRepository::class)
 */
class NoticeBoard
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $start;

    /**
     * @ORM\Column(type="date")
     */
    private $end;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $statut;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $addDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="noticeBoards")
     */
    private $recorder;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = false;

    /**
     * @ORM\OneToMany(targetEntity=NoticeBoardEmployee::class, mappedBy="noticeBoard")
     */
    private $noticeBoardEmployees;

    public function __construct()
    {
        $this->noticeBoardEmployees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Collection|NoticeBoardEmployee[]
     */
    public function getNoticeBoardEmployees(): Collection
    {
        return $this->noticeBoardEmployees;
    }

    public function addNoticeBoardEmployee(NoticeBoardEmployee $noticeBoardEmployee): self
    {
        if (!$this->noticeBoardEmployees->contains($noticeBoardEmployee)) {
            $this->noticeBoardEmployees[] = $noticeBoardEmployee;
            $noticeBoardEmployee->setNoticeBoard($this);
        }

        return $this;
    }

    public function removeNoticeBoardEmployee(NoticeBoardEmployee $noticeBoardEmployee): self
    {
        if ($this->noticeBoardEmployees->removeElement($noticeBoardEmployee)) {
            // set the owning side to null (unless already changed)
            if ($noticeBoardEmployee->getNoticeBoard() === $this) {
                $noticeBoardEmployee->setNoticeBoard(null);
            }
        }

        return $this;
    }
}
