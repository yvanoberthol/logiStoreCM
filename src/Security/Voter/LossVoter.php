<?php

namespace App\Security\Voter;

use App\Entity\Loss;
use App\Util\RoleConstant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class LossVoter extends Voter
{
    private const DELETE = 'LOSS_DELETE';
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct( AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof Loss;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$this->authorizationChecker
            ->isGranted('PERMISSION_VERIFY','LOSS_DELETE')){
            return false;
        }

        if ($subject->getProductStock()->getWithdraw()){
            return false;
        }

        if ($user->getRole()->getRank() > 1){
            return true;
        }

        $lossRecorder = $subject->getRecorder();
        $currentUser = $token->getUser();

        return ($lossRecorder->getId() === $currentUser->getId());

    }
}
