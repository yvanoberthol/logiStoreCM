<?php

namespace App\Tests;

use App\Entity\ProductSale;
use App\Entity\Sale;
use App\Repository\PaymentMethodRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartTest extends WebTestCase
{

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var ProductService
     */
    private $productService;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * CartTest constructor.
     * @param ProductRepository $productRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param UserRepository $userRepository
     * @param CartService $cartService
     * @param ProductService $productService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ProductRepository $productRepository,
                                PaymentMethodRepository $paymentMethodRepository,
                                UserRepository $userRepository,
                                CartService $cartService,
                                ProductService $productService,
                                EntityManagerInterface $entityManager)
    {
        $this->productRepository = $productRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->userRepository = $userRepository;
        $this->cartService = $cartService;
        $this->productService = $productService;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Exception
     */
    public function testCartRegistration(): bool
    {

        $faker = Factory::create();
        $products = $this->productRepository->findAll();
        $paymentMethods = $this->paymentMethodRepository->findAll();
        $cashiers= $this->userRepository
            ->findUserByRole('ROLE_CASHIER');

        // clear a cart
        $this->cartService->removeAll();
        $productDispos = $this->productService
            ->getProductByStockNotFinish($products);

        $maxProducts = count($productDispos);
        $nbProductSales = random_int(1,3);

        if ($maxProducts <= $nbProductSales){
            $nbProductSales = $maxProducts-1;
        }

        //add product in cart
        do{
            $productsRecovers = [];
            for ($j=0;$j < $nbProductSales;$j++){
                $product = $productDispos[random_int(0,$maxProducts-1)];

                if(!in_array($product->getId(),$productsRecovers,true)){
                    $qtySelected = random_int(1,2);

                    if (!is_numeric($product->getId())){
                        return false;
                    }

                    if ($qtySelected >= $product->getStock()){
                        $qtySelected = $product->getStock();
                    }
                    $this->cartService->changeQty($product->getId(),$qtySelected);
                    $productsRecovers[] = $product->getId();
                }
            }
        }while(empty($productsRecovers));

        $amountReceived = (int) "10E3";
        $discount= "200S";

        if (!is_numeric($amountReceived)
            || ($discount !== null
                && !is_numeric((int) $discount))){

            return false;
        }

        $itemProfit = $this->cartService
            ->getItemProfit();

        $sale = new Sale();

        $sale->setAmount($this->cartService->getTotalWithTax());
        $sale->setTaxAmount($this->cartService->getTotalTax());
        $sale->setProfit($itemProfit['total']);

        if (random_int(0,1)){
            $sale->setAmountReceived(
                $this->cartService->getTotalWithTax()
            );
        }else{
            $rest = $this->cartService->getTotalWithTax() % 500;
            $amountBefore =
                $this->cartService->getTotalWithTax() - $rest;

            if (($amountBefore / 500) <= 1){
                $amountReceived = 500;
            }else{
                $amountReceived =
                    $amountBefore + (random_int(1,5) * 500);
            }
            $sale->setAmountReceived($amountReceived);
        }

        $sale->setRecorder(
            $cashiers[
                random_int(0,count($cashiers)-1)
            ]
        );
        $sale->setPaymentMethod(
            $paymentMethods[
                random_int(0,count($paymentMethods)-1)
            ]
        );
        $sale->setAddDate(
            $faker
                ->dateTimeBetween(
                    '2022-04-01',
                    '2022-04-30')
        );
        $this->entityManager->persist($sale);


        $cartProducts = $this->cartService->getFullCart();
        foreach ($cartProducts as $item){
            $productStockSales = $this->cartService->getProductStocks($item);

            $productSale = new ProductSale();
            $productSale->setProduct($item['product']);
            $productSale->setQty($item['qty']);
            $productSale->setSale($sale);
            foreach ($productStockSales as $productStockSale){
                $this->entityManager->persist($productStockSale);
                $productSale->addProductStockSale($productStockSale);
            }
            $productSale->setProfit($itemProfit[$item['product']->getId()]);
            $productSale->setUnitPrice($item['product']->getSellPrice());
            $this->entityManager->persist($productSale);
        }

        $this->entityManager->flush();

        return true;
    }
}
