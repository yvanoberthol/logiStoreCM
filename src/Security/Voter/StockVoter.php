<?php

namespace App\Security\Voter;

use App\Entity\Stock;
use App\Util\RoleConstant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class StockVoter extends Voter
{
    private const DELETE = 'STOCK_DELETE';

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof Stock;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($subject->getProductStocks()){
            foreach ($subject->getProductStocks() as $productStock){
                if (count($productStock->getProductStockSales()) > 0){
                    return false;
                }
            }
        }

        if ($user->getRole()->getRank() > 1){
            return true;
        }

        $stockRecorder = $subject->getRecorder();
        $currentUser = $token->getUser();

        return ($stockRecorder->getId() === $currentUser->getId());

    }
}
