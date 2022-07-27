<?php

namespace App\Security\Voter;

use App\Entity\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionVoter extends Voter
{
    private const PERMISSION_VERIFY = 'PERMISSION_VERIFY';

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::PERMISSION_VERIFY;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (empty($user->getRole()->getPermissions())) {
            return false;
        }

        $permissions = $user->getRole()->getPermissions()->map(static function(Permission $permission){
            return $permission->getCode();
        })->toArray();

        return in_array($subject, $permissions, true);

    }
}
