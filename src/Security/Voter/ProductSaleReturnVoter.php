<?php

namespace App\Security\Voter;

use App\Entity\ProductSaleReturn;
use App\Service\ProductService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductSaleReturnVoter extends Voter
{
    /**
     * @var ProductService
     */
    private $productService;

    /**
     * ProductSaleReturnVoter constructor.
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    private const DELETE = 'SALE_PRODUCT_RETURN_DELETE';

    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::DELETE
            && $subject instanceof ProductSaleReturn;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($subject->getStockable()){
            $productStock = $this->productService
                ->countQtyRemaining($subject->getProductStockSale()
                    ->getProductStock()
                );

            $qtyRemainingWithoutStockReturn =
                $productStock->getQtyRemaining() - $subject->getQty();

            if ($subject->getQty() > $qtyRemainingWithoutStockReturn){
                return false;
            }
        }

        return $user->getRole()->getRank() >= 2;

    }
}
