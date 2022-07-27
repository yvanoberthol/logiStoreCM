<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Entity\Sale;
use App\Entity\ProductSale;
use App\Entity\Setting;
use App\Entity\Tax;
use App\Extension\AppExtension;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\SaleRepository;
use App\Repository\TaxRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\ProductService;
use App\Util\CustomerTypeConstant;
use App\Util\ModuleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class RestCartController extends AbstractController
{
    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var TaxRepository
     */
    private $taxRepository;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductService
     */
    private $productService;
    /**
     * @var AppExtension
     */
    private $appExtension;
    /**
     * @var Setting
     */
    private $setting;

    /**
     * RestCartController constructor.
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param CustomerRepository $customerRepository
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @param TaxRepository $taxRepository
     * @param CartService $cartService
     * @param RequestStack $requestStack
     * @param AppExtension $appExtension
     */
    public function __construct(PaymentMethodRepository $paymentMethodRepository,
                                CustomerRepository $customerRepository,
                                ProductRepository $productRepository,
                                ProductService $productService,
                                TaxRepository $taxRepository,
                                CartService $cartService,
                                RequestStack $requestStack,
                                AppExtension $appExtension)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->customerRepository = $customerRepository;
        $this->taxRepository = $taxRepository;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->appExtension = $appExtension;
        $this->setting = $requestStack->getSession()->get('setting');
    }


    /**
     * @Route("/cart/add", name="rest_cart_add",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function addToCart(Request $request): Response
    {

        if (!is_numeric((int) $request->get('id'))
            || !is_numeric((int) $request->get('qty'))){
            return $this->json(null,200);
        }

        $result =  $this->cartService->add((int) $request->get('id'),
            (int) $request->get('qty'));

        if (!$result){
          return $this->json(null,200);
        }

        $data = $this->getInfoCart();

        return $this->json($data,200);
    }

    /**
     * @Route("/cart/change", name="rest_cart_change",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function changeToCart(Request $request): Response
    {

        if (!is_numeric((int) $request->get('id'))
            || !is_numeric((int) $request->get('qty'))){
            return $this->json(null,200);
        }

        $result =  $this->cartService->changeQty((int) $request->get('id'),
            (int) $request->get('qty'));

        if (!$result){
            return $this->json(null,200);
        }

        $data = $this->getInfoCart();

        return $this->json($data,200);
    }

    /**
     * @Route("/cart/tax/set", name="rest_cart_tax_set",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function setTax(Request $request): Response
    {

        if (!is_numeric((int) $request->get('id'))){
            return $this->json(null,200);
        }

        $result =  $this->cartService->setTax((int) $request->get('id'));

        if (!$result){
            return $this->json(null,200);
        }

        return $this->json($result,200);
    }


    /**
     * @Route("/cart/remove", name="rest_cart_remove",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function removeToCart(Request $request): Response
    {

        if (!is_numeric((int) $request->get('id'))){
            return $this->json(null,200);
        }

        $this->cartService->remove((int) $request->get('id'));

        $data = $this->getInfoCart();
        return $this->json($data,200);
    }

    /**
     * @Route("/cart/removeAll", name="rest_cart_removeAll",methods={"GET","POST"})
     * @return Response
     */
    public function removeToCartAll(): Response
    {
        $this->cartService->removeAll();

        $data = $this->getInfoCart();
        return $this->json($data,200);
    }


    /**
     * @Route("/cart/validate", name="rest_cart_validate",methods={"GET","POST"})
     * @param UserRepository $userRepository
     * @param SaleRepository $saleRepository
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function validateCart(UserRepository $userRepository,
                                 SaleRepository $saleRepository,
                                 Request $request,
                                 EntityManagerInterface $entityManager): Response
    {

        $numInvoice = (empty($request->get('numInvoice')))?null:$request->get('numInvoice');

        if ($numInvoice !== null){
            $saleByNumInvoice = $saleRepository->findOneBy(['numInvoice'=>$numInvoice]);
            if ($saleByNumInvoice !== null) {
                return $this->json('num_invoice_invalid', 200);
            }
        }


        $amountReceived = ($request->get('amountReceived') !== null)
            ?abs((int) $request->get('amountReceived')): 0;

        $discount = ($request->get('discount') !== null
            && $this->isGranted('PERMISSION_VERIFY','SALE_WITH_DISCOUNT'))
            ?abs((int) $request->get('discount')): 0;

        if (!is_numeric((int) $request->get('amountReceived'))
            || ($request->get('discount') !== null
                && !is_numeric((int) $request->get('discount')))){

            return $this->json(null,200);
        }

        if (empty($this->cartService->getFullCart())){
            return $this->json(null,200);
        }

        $totalwithDiscount = $this->cartService->getTotalWithTax() - $discount;

        if ($amountReceived <= 0
            && (!$this->setting->getWithPartialPayment()
                || !$this->isGranted('PERMISSION_VERIFY','SALE_WITH_PARTIAL_PAYMENT'))){
            return $this->json(null,200);
        }

        $customer = null;
        if ($request->get('customer')){
            $customer = $this->customerRepository
                ->find((int) $request->get('customer'));
        }

        if ($amountReceived < $totalwithDiscount &&
            (!$this->setting->getWithPartialPayment()
            || !$this->isGranted('PERMISSION_VERIFY','SALE_WITH_PARTIAL_PAYMENT'))){
            return $this->json(null,200);
        }

        if ($customer === null
            && $amountReceived < $totalwithDiscount &&
            (!$this->setting->getWithPartialPayment()
                || !$this->isGranted('PERMISSION_VERIFY','SALE_WITH_PARTIAL_PAYMENT'))){
            return $this->json('customer_invalid',200);
        }

        $dateSale = !empty($request->get('saleDate'))?new DateTime($request->get('saleDate')) :new DateTime();

        $paymentMethod = $this->paymentMethodRepository
            ->find((int) $request->get('paymentMethod'));

        $user = $userRepository
            ->find((int) $request->get('userId'));

        $sale = new Sale();
        $sale->setNumInvoice($numInvoice);
        $sale->setAmount($totalwithDiscount);
        $sale->setTaxAmount($this->cartService->getTotalTax());
        $sale->setAmountReceived($amountReceived);
        $sale->setPaymentMethod($paymentMethod);
        $sale->setAddDate($dateSale);
        $sale->setRecorder($user);
        $sale->setCustomer($customer);
        if ($this->setting->getWithDiscount()){
            $sale->setDiscount($discount);
        }

        foreach ($this->cartService->getTaxs() as $tax){
            $sale->addTax($tax);
        }

        $cartProducts = $this->cartService->getFullCart();

        $totalProfit = 0;
        foreach ($cartProducts as $item){
            $productStockSales = $this->cartService->getProductStocks($item);
            $productSale = new ProductSale();
            $productSale->setProduct($item['product']);
            $productSale->setQty($item['qty']);

            if ($this->setting->getWithClubPoint() &&
                $this->appExtension->moduleExists(ModuleConstant::MODULES['club_point'])){
                $productSale->setPoint($item['product']->getPoint());
            }

            if ($this->setting->getWithDiscount()){
                $productSale
                    ->setDiscount(($item['qty'] * $item['product']->getDiscount()));
            }

            $productSale->setSale($sale);
            foreach ($productStockSales as $productStockSale){
                $productStockSale->setUnitPrice($item['sellPrice']);
                $entityManager->persist($productStockSale);
                $productSale->addProductStockSale($productStockSale);
            }

            $productSale->setUnitPrice($item['sellPrice']);
            $entityManager->persist($productSale);

            $totalProfit += $productSale->getProfit();
        }

        $sale->setProfit($totalProfit - $discount);
        $entityManager->persist($sale);
        $entityManager->flush();

        // clear a cart
        $this->cartService->removeAll();


        $model['products'] = $this->getProductList();
        $model['cart'] = $this->getInfoCart();
        $model['saleId'] = $sale->getId();

        return $this->json($model,200);

    }

    /**
     * @Route("/cart/get", name="rest_cart_get")
     * @return Response
     */
    public function getCart(): Response
    {
        return $this->json($this->getInfoCart()
            ,200
        );
    }

    /**
     * @Route("/product/barcode", name="rest_product_barcode")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getProductBarcode(Request $request): Response
    {
        return $this->json($this->getProductByBarcode($request->get('barcode'))
            ,200
        );
    }

    private function getInfoCart(): Response
    {
        $model['items'] = $this->cartService->getFullCart();
        $model['taxs'] = $this->taxRepository->findBy(['status' => true]);
        $model['total'] = $this->cartService->getTotal();
        $model['totalWithTax'] = $this->cartService->getTotalWithTax();
        $model['taxCart'] = $this->cartService->getTaxs();
        $model['paymentMethods'] = $this->paymentMethodRepository
            ->findBy(['status' => true]);

        return $this->render('partials/cart.html.twig',$model);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getProductList(): array
    {
        $products = $this->productRepository->findBy([],['addDate' => 'DESC']);
        $model['products'] =  $this->productService->countStocks($products);

        return array_map(static function ($product){
            return ProductDto::createFromEntity($product);
        }, $model['products']);
    }

    /**
     * @param $barcode
     * @return ProductDto
     */
    private function getProductByBarcode($barcode): ProductDto
    {
        return ProductDto
            ::createFromEntity($this->productRepository->findOneBy(['qrCode' => $barcode]));
    }
}
