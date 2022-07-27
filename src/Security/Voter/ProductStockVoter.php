<?php

namespace App\Security\Voter;

use App\Entity\ProductStock;
use App\Util\RoleConstant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductStockVoter extends Voter
{
    private const DELETE = 'PRODUCT_STOCK_DELETE';

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof ProductStock;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (count($subject->getProductStockSales()->toArray()) > 0){
            return false;
        }

        if ($user->getRole()->getRank() > 1){
            return true;
        }


        $stockRecorder = $subject->getStock()->getRecorder();
        $currentUser = $token->getUser();

        return ($stockRecorder->getId() === $currentUser->getId());

    }
}
