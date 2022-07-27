<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordUpdate
{

    private $oldpassword;

    /**
     * @Assert\Length(min=8,minMessage="entity.passwordUpdate.newpassword")
     */
    private $newpassword;

    /**
     * @Assert\EqualTo(propertyPath="newpassword",message="entity.passwordUpdate.confirmpassword")
     */
    private $confirmpassword;


    public function getOldpassword(): ?string
    {
        return $this->oldpassword;
    }

    public function setOldpassword(string $oldpassword): self
    {
        $this->oldpassword = $oldpassword;

        return $this;
    }

    public function getNewpassword(): ?string
    {
        return $this->newpassword;
    }

    public function setNewpassword(string $newpassword): self
    {
        $this->newpassword = $newpassword;

        return $this;
    }

    public function getConfirmpassword(): ?string
    {
        return $this->confirmpassword;
    }

    public function setConfirmpassword(string $confirmpassword): self
    {
        $this->confirmpassword = $confirmpassword;

        return $this;
    }
}
