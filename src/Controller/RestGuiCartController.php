<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Entity\Sale;
use App\Entity\ProductSale;
use App\Entity\Setting;
use App\Entity\Tax;
use App\Extension\AppExtension;
use App\Repository\CustomerRepository;
use App\Repository\ProductPriceRepository;
use App\Repository\ProductRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\TaxRepository;
use App\Repository\UserRepository;
use App\Service\CartGuiService;
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
class RestGuiCartController extends AbstractController
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
     * @var CartGuiService
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
     * @param CartGuiService $cartService
     * @param RequestStack $requestStack
     * @param AppExtension $appExtension
     */
    public function __construct(PaymentMethodRepository $paymentMethodRepository,
                                CustomerRepository $customerRepository,
                                ProductRepository $productRepository,
                                ProductService $productService,
                                TaxRepository $taxRepository,
                                CartGuiService $cartService,
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
     * @Route("/cart/gui/add", name="rest_cart_gui_add",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function addToCart(Request $request): Response
    {
        //dd($request->get('productStocks'));
        $result =  $this->cartService->changeQty((int) $request->get('id'),
            $request->get('productStocks'));

        if (!$result){
          return $this->json(null,200);
        }

        $data = $this->getInfoCart();

        return $this->json($data,200);
    }

    /**
     * @Route("/cart/gui/tax/set", name="rest_cart_gui_tax_set",methods={"GET","POST"})
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
     * @Route("/cart/gui/remove", name="rest_cart_gui_remove",methods={"GET","POST"})
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
     * @Route("/cart/gui/removeAll", name="rest_cart_gui_removeAll",methods={"GET","POST"})
     * @return Response
     */
    public function removeToCartAll(): Response
    {
        $this->cartService->removeAll();

        $data = $this->getInfoCart();
        return $this->json($data,200);
    }

    /**
     * @Route("/cart/gui/product/price", name="rest_cart_gui_price")
     * @param Request $request
     * @param ProductPriceRepository $productPriceRepository
     * @return Response
     */
    public function getProductPrice(Request $request,
                                    ProductPriceRepository $productPriceRepository): Response
    {
        $product = $this->productRepository->find((int)$request->get('product'));

        $sellPrice = $product->getSellPrice();
        if ($product !== null && count($product->getProductPrices()) > 0){
            $productPrice = $productPriceRepository
                ->findOneByProductAndQty($product,(int)$request->get('qty'));
            if ($productPrice !== null){
                $sellPrice =$productPrice->getUnitPrice();
            }
        }


        return $this->json($sellPrice,200);
    }


    /**
     * @Route("/cart/gui/validate", name="rest_cart_gui_validate",methods={"GET","POST"})
     * @param UserRepository $userRepository
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function validateCart(UserRepository $userRepository,
                                 Request $request,
                                 EntityManagerInterface $entityManager): Response
    {


        $amountReceived = abs((float) $request->get('amountReceived'));
        $discount = ($request->get('discount') !== null
        && $this->isGranted('PERMISSION_VERIFY','SALE_WITH_DISCOUNT'))
            ?abs((float) $request->get('discount')): 0;

        if (!is_numeric((int) $request->get('amountReceived'))
            || ($request->get('discount') !== null
                && !is_numeric((int) $request->get('discount')))){

            return $this->json(null,200);
        }

        if (empty($this->cartService->getFullCartGui())){
            return $this->json(null,200);
        }

        $totalwithDiscount = $this->cartService->getTotalWithTax() - $discount;

        if ($amountReceived <= 0 && (!$this->setting->getWithPartialPayment()
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
            && $amountReceived < $totalwithDiscount
            && (!$this->setting->getWithPartialPayment()
            || !$this->isGranted('PERMISSION_VERIFY','SALE_WITH_PARTIAL_PAYMENT'))){
            return $this->json('customer_invalid',200);
        }

        $dateSale = !empty($request->get('saleDate'))?new DateTime($request->get('saleDate')) :new DateTime();

        $paymentMethod = $this->paymentMethodRepository
            ->find((int) $request->get('paymentMethod'));

        $user = $userRepository
            ->find((int) $request->get('userId'));

        $itemProfit = $this->cartService->getItemProfit();


        $sale = new Sale();
        $sale->setAmount($totalwithDiscount);
        $sale->setTaxAmount($this->cartService->getTotalTax());
        $sale->setAmountReceived($amountReceived);
        $sale->setProfit($itemProfit['total'] - $discount);
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
        $entityManager->persist($sale);

        $cartProducts = $this->cartService->getFullCartGui();

        foreach ($cartProducts as $item){
            $productStockSales = $this->cartService->getProductStocks($item);
            $productSale = new ProductSale();
            $productSale->setProduct($item['product']);
            $productSale->setQty($item['qty']);

            if ($this->setting->getWithClubPoint() &&
                $this->appExtension->moduleExists(ModuleConstant::MODULES['club_point'])){
                $productSale->setPoint($item['product']->getPoint());
            }

            if ($this->setting->getProductWithDiscount()){
                $productSale
                    ->setDiscount(($item['qty'] * $item['product']->getDiscount()));
            }

            $productSale->setSale($sale);
            foreach ($productStockSales as $productStockSale){
                $productStockSale->setUnitPrice($item['sellPrice']);
                $entityManager->persist($productStockSale);
                $productSale->addProductStockSale($productStockSale);
            }
            //$productSale->setProfit($itemProfit[$item['product']->getId()]);
            $productSale->setUnitPrice($item['sellPrice']);
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
     * @Route("/cart/gui/get", name="rest_cart_gui_get")
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

        $model['items'] = $this->cartService->getFullCartGui();
        $model['taxs'] = $this->taxRepository->findBy(['status' => true]);
        $model['total'] = $this->cartService->getTotal();
        $model['totalWithTax'] = $this->cartService->getTotalWithTax();
        $model['taxCart'] = $this->cartService->getTaxs();
        $model['paymentMethods'] = $this->paymentMethodRepository
            ->findBy(['status' => true]);

        return $this->render('partials/cartgui.html.twig',$model);
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
