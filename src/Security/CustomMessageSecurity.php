<?php


namespace App\Security;


use Symfony\Component\Security\Core\Exception\AccountStatusException;

class CustomMessageSecurity extends AccountStatusException
{
    public function getMessageKey(): ?string
    {
        return 'account is disabled';
    }
}