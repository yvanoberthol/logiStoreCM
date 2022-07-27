<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Entity\Sale;
use App\Entity\ProductSale;
use App\Entity\Setting;
use App\Extension\AppExtension;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\SaleRepository;
use App\Repository\TaxRepository;
use App\Repository\UserRepository;
use App\Service\CartWholeSalerService;
use App\Service\ProductService;
use App\Util\ModuleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class RestWholesalerCartController extends AbstractController
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
     * @var CartWholeSalerService
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
     * @param CartWholeSalerService $cartService
     * @param RequestStack $requestStack
     * @param AppExtension $appExtension
     */
    public function __construct(PaymentMethodRepository $paymentMethodRepository,
                                CustomerRepository $customerRepository,
                                ProductRepository $productRepository,
                                ProductService $productService,
                                TaxRepository $taxRepository,
                                CartWholeSalerService $cartService,
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
     * @Route("/cart/wholesaler/add", name="rest_cart_wholesaler_add",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function addToCart(Request $request): Response
    {
        $result =  $this->cartService->changeQty((int) $request->get('id'),
            $request->get('qty'),$request->get('price'));

        if (!$result){
          return $this->json(null,200);
        }

        $data = $this->getInfoCart();

        return $this->json($data,200);
    }

    /**
     * @Route("/cart/wholesaler/tax/set", name="rest_cart_wholesaler_tax_set",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function setTax(Request $request): Response
    {

        $result =  $this->cartService->setTax((int) $request->get('id'));

        if (!$result){
            return $this->json(null,200);
        }

        return $this->json($result,200);
    }


    /**
     * @Route("/cart/wholesaler/remove", name="rest_cart_wholesaler_remove",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function removeToCart(Request $request): Response
    {
        $this->cartService->remove((int) $request->get('id'));

        $data = $this->getInfoCart();
        return $this->json($data,200);
    }

    /**
     * @Route("/cart/wholesaler/removeAll", name="rest_cart_wholesaler_removeAll",methods={"GET","POST"})
     * @return Response
     */
    public function removeToCartAll(): Response
    {
        $this->cartService->removeAll();

        $data = $this->getInfoCart();
        return $this->json($data,200);
    }


    /**
     * @Route("/cart/wholesaler/validate", name="rest_cart_wholesaler_validate",methods={"GET","POST"})
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
            ?abs((float) $request->get('discount')): 0;

        if (!is_numeric((int) $request->get('amountReceived'))
            || ($request->get('discount') !== null
                && !is_numeric((int) $request->get('discount')))){

            return $this->json(null,200);
        }

        if (empty($this->cartService->getFullCartWholesaler())){
            return $this->json(null,200);
        }

        if ($amountReceived <= 0 && (!$this->setting->getWithPartialPayment()
                || !$this->isGranted('PERMISSION_VERIFY','SALE_WHOLE_WITH_PARTIAL_PAYMENT'))){
            return $this->json(null,200);
        }

        $totalwithDiscount = $this->cartService->getTotalWithTax() - $discount;

        $dateSale = !empty($request->get('saleDate'))?new DateTime($request->get('saleDate')) :new DateTime();

        $paymentMethod = $this->paymentMethodRepository
            ->find((int) $request->get('paymentMethod'));

        $user = $userRepository
            ->find((int) $request->get('userId'));

        $customer = null;
        if ($request->get('customer')){
            $customer = $this->customerRepository
                ->find((int) $request->get('customer'));
        }

        if ($amountReceived < $totalwithDiscount &&
            (!$this->setting->getWithPartialPayment()
                || !$this->isGranted('PERMISSION_VERIFY','SALE_WHOLE_WITH_PARTIAL_PAYMENT'))){
            return $this->json(null,200);
        }

        if ($customer === null &&
            $amountReceived < $totalwithDiscount &&
            (!$this->setting->getWithPartialPayment()
                || !$this->isGranted('PERMISSION_VERIFY','SALE_WHOLE_WITH_PARTIAL_PAYMENT'))){
            return $this->json('customer_invalid',200);
        }

        $itemProfit = $this->cartService->getItemProfit();


        $sale = new Sale();
        $sale->setNumInvoice($numInvoice);
        $sale->setAmount($totalwithDiscount);
        $sale->setTaxAmount($this->cartService->getTotalTax());
        $sale->setAmountReceived($amountReceived);
        $sale->setProfit($itemProfit['total'] - $discount);
        $sale->setPaymentMethod($paymentMethod);
        $sale->setAddDate($dateSale);
        $sale->setRecorder($user);
        $sale->setCustomer($customer);

        if ($this->setting->getWithDiscount()){
            $discount = ($request->get('discount') !== null)?abs((int) $request->get('discount')): 0;
            $sale->setDiscount($discount);
        }

        foreach ($this->cartService->getTaxs() as $tax){
            $sale->addTax($tax);
        }
        $entityManager->persist($sale);

        $cartProducts = $this->cartService->getFullCartWholesaler();

        foreach ($cartProducts as $item){
            $productStockSales = $this->cartService->getProductStocks($item);
            $productSale = new ProductSale();
            $productSale->setProduct($item['product']);
            $productSale->setQty($item['qty']);

            if ($this->setting->getWithClubPoint() &&
                $this->appExtension->moduleExists(ModuleConstant::MODULES['club_point'])){
                $productSale->setPoint($item['product']->getWholePoint());
            }

            if ($this->setting->getProductWithDiscount()){
                $productSale
                    ->setDiscount(($item['qty'] * $item['product']->getWholeDiscount()));
            }

            $productSale->setSale($sale);
            foreach ($productStockSales as $productStockSale){
                $productStockSale->setUnitPrice($item['unitPrice']);
                $entityManager->persist($productStockSale);
                $productSale->addProductStockSale($productStockSale);
            }
            $productSale->setProfit($itemProfit[$item['product']->getId()]);
            $productSale->setUnitPrice($item['unitPrice']);
            $entityManager->persist($productSale);
        }

        $entityManager->flush();

        // clear a cart
        $this->cartService->removeAll();


        $model['products'] = $this->getProductList();
        $model['cart'] = $this->getInfoCart();
        $model['saleId'] = $sale->getId();

        return $this->json($model,200);

    }

    /**
     * @Route("/cart/wholesaler/get", name="rest_cart_wholesaler_get")
     * @return Response
     */
    public function getCart(): Response
    {
        return $this->json($this->getInfoCart()
            ,200
        );
    }

    private function getInfoCart(): Response
    {
        $model['items'] = $this->cartService->getFullCartWholesaler();
        $model['taxs'] = $this->taxRepository->findBy(['status' => true]);
        $model['total'] = $this->cartService->getTotal();
        $model['totalWithTax'] = $this->cartService->getTotalWithTax();
        $model['taxCart'] = $this->cartService->getTaxs();
        $model['paymentMethods'] = $this->paymentMethodRepository
            ->findBy(['status' => true]);

        return $this->render('partials/cartwholesaler.html.twig',$model);
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
}
