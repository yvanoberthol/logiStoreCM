<?php

namespace App\Security\Voter;

use App\Entity\StockPayment;
use App\Util\RoleConstant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class StockPaymentVoter extends Voter
{
    private const DELETE = 'STOCK_PAYMENT_REMOVE';

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof StockPayment;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($user->getRole()->getRank() > 1){
            return true;
        }

        $stockPaymentRecorder = $subject->getRecorder();
        $currentUser = $token->getUser();

        return ($stockPaymentRecorder->getId() === $currentUser->getId());

    }
}
