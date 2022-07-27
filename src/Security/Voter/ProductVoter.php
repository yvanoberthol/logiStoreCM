<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Service\ProductService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductVoter extends Voter
{
    private const DELETE = 'PRODUCT_DELETE';

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var ProductService
     */
    private $productService;

    public function __construct( ProductService $productService,
                                 AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->productService = $productService;
    }

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof Product;
    }

    /**
     * @param $attribute
     * @param $subject
     * @param TokenInterface $token
     * @return bool
     * @throws \Exception
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$this->authorizationChecker
            ->isGranted('PERMISSION_VERIFY','PRODUCT_DELETE')){
            return false;
        }

        /*$product = $this->productService->countStock($subject);

        if (!$product->getDeletable()){
            return false;
        }*/

        if ($user->getRole()->getRank() < 2){
            return false;
        }

        return true;

    }
}
