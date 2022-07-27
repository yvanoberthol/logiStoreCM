<?php

namespace App\Entity;

use App\Repository\NoticeBoardEmployeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NoticeBoardEmployeeRepository::class)
 */
class NoticeBoardEmployee
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=NoticeBoard::class, inversedBy="noticeBoardEmployees")
     * @ORM\JoinColumn(nullable=false)
     */
    private $noticeBoard;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="noticeBoardEmployees")
     * @ORM\JoinColumn(nullable=false)
     */
    private $employee;

    /**
     * @ORM\Column(type="boolean")
     */
    private $seen = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNoticeBoard(): ?NoticeBoard
    {
        return $this->noticeBoard;
    }

    public function setNoticeBoard(?NoticeBoard $noticeBoard): self
    {
        $this->noticeBoard = $noticeBoard;

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

    public function getSeen(): ?bool
    {
        return $this->seen;
    }

    public function setSeen(bool $seen): self
    {
        $this->seen = $seen;

        return $this;
    }
}
