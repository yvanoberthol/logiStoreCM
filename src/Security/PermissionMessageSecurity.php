<?php


namespace App\Security;


use Symfony\Component\Security\Core\Exception\AccountStatusException;

class PermissionMessageSecurity extends AccountStatusException
{
    public function getMessageKey(): ?string
    {
        return 'no permission for this user';
    }
}
