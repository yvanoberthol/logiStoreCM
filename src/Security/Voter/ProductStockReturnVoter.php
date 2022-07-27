<?php

namespace App\Security\Voter;

use App\Entity\ProductStockReturn;
use App\Service\ProductService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductStockReturnVoter extends Voter
{
    /**
     * @var ProductService
     */
    private $productService;

    /**
     * ProductStockReturnVoter constructor.
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    private const DELETE = 'STOCK_PRODUCT_RETURN_DELETE';

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof ProductStockReturn;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        $productStock = $this->productService
            ->countQtyRemaining($subject->getProductStock()
            );

        $qtyRemainingWithoutStockReturn =
            $productStock->getQtyRemaining() - $subject->getQty();

        if ($subject->getQty() > $qtyRemainingWithoutStockReturn){
            return false;
        }

        return $user->getRole()->getRank() >= 2;

    }
}
