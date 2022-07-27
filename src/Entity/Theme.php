<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ThemeRepository::class)
 */
class Theme
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $backcolorSideMenu;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $colorSideMenuLink;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $generalColorLight;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $generalColorDark;

    /**
     * @ORM\Column(type="boolean")
     */
    private $deletable;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBackcolorSideMenu(): ?string
    {
        return $this->backcolorSideMenu;
    }

    public function setBackcolorSideMenu(string $backcolorSideMenu): self
    {
        $this->backcolorSideMenu = $backcolorSideMenu;

        return $this;
    }

    public function getColorSideMenuLink(): ?string
    {
        return $this->colorSideMenuLink;
    }

    public function setColorSideMenuLink(string $colorSideMenuLink): self
    {
        $this->colorSideMenuLink = $colorSideMenuLink;

        return $this;
    }

    public function getGeneralColorLight(): ?string
    {
        return $this->generalColorLight;
    }

    public function setGeneralColorLight(string $generalColorLight): self
    {
        $this->generalColorLight = $generalColorLight;

        return $this;
    }

    public function getGeneralColorDark(): ?string
    {
        return $this->generalColorDark;
    }

    public function setGeneralColorDark(string $generalColorDark): self
    {
        $this->generalColorDark = $generalColorDark;

        return $this;
    }

    public function getDeletable(): ?bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): self
    {
        $this->deletable = $deletable;

        return $this;
    }
}
