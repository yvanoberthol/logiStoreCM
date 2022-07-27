<?php

namespace App\Security\Voter;

use App\Entity\Sale;
use App\Util\GlobalConstant;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SaleVoter extends Voter
{
    private const DELETE = 'SALE_DELETE';
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * SaleVoter constructor.
     * @param RequestStack $requestStack
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(RequestStack $requestStack,
                                AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->session = $requestStack->getSession();
        $this->authorizationChecker = $authorizationChecker;
    }


    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof Sale;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {

        $currentUser = $token->getUser();
        $saleRecorder = $subject->getRecorder();

        // if the user is anonymous, do not grant access
        if (!$currentUser instanceof UserInterface) {
            return false;
        }

        if ($this->authorizationChecker
            ->isGranted('PERMISSION_VERIFY','SALE_DELETE_ALL')){
            return true;
        }

        if (!$this->authorizationChecker
                ->isGranted('PERMISSION_VERIFY','SALE_DELETE') &&
            !$this->authorizationChecker
                ->isGranted('PERMISSION_VERIFY','SALE_SOFT_DELETE')){
            return false;
        }

        try {

            $setting = $this->session->get('setting');
            if ($setting !== null &&
                !empty($setting->getTimeValiditySale()) &&
                GlobalConstant::limitPassed($subject->getAddDate(),
                    $setting->getTimeValiditySale())) {
                    return false;
                }

        } catch (Exception $e) {
            return false;
        }

        return $saleRecorder->getId() === $currentUser->getId();

    }
}
