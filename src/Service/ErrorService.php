<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ErrorService
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * ErrorService constructor.
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param FlashBagInterface $flashBag
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker,
                                FlashBagInterface $flashBag)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->flashBag = $flashBag;
    }

    public function denyAccessUnlessGranted($attribute, $subject = null,
                                            string $message = 'Access Denied.'): bool
    {
        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            $this->flashBag->add('danger',$message);
            return true;
        }

        return false;
    }
}
