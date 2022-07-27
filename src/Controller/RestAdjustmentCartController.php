<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Entity\Adjustment;
use App\Entity\ProductAdjust;
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
use App\Service\CartAdjustmentService;
use App\Service\ProductService;
use App\Util\ModuleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class RestAdjustmentCartController extends AbstractController
{
    
    /**
     * @var CartAdjustmentService
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
     * RestCartController constructor.
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @param CartAdjustmentService $cartService
     */
    public function __construct(ProductRepository $productRepository,
                                ProductService $productService,
                                CartAdjustmentService $cartService)
    {
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->productService = $productService;
    }


    /**
     * @Route("/cart/adjustment/add", name="rest_cart_adjustment_add",methods={"GET","POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function addToCart(Request $request): Response
    {
        $result =  $this->cartService->changeQty((int) $request->get('id'),
            $request->get('qty'));

        if (!$result){
          return $this->json(null,200);
        }

        $data = $this->getInfoCart();

        return $this->json($data,200);
    }


    /**
     * @Route("/cart/adjustment/remove", name="rest_cart_adjustment_remove",methods={"GET","POST"})
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
     * @Route("/cart/adjustment/removeAll", name="rest_cart_adjustment_removeAll",methods={"GET","POST"})
     * @return Response
     */
    public function removeToCartAll(): Response
    {
        $this->cartService->removeAll();

        $data = $this->getInfoCart();
        return $this->json($data,200);
    }


    /**
     * @Route("/cart/adjustment/validate", name="rest_cart_adjustment_validate",methods={"GET","POST"})
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

        if (empty($this->cartService->getFullCartAdjustment())){
            return $this->json(null,200);
        }

        $dateAdjustment = !empty($request->get('saleDate'))?new DateTime($request->get('saleDate')) :new DateTime();

        $user = $userRepository
            ->find((int) $request->get('userId'));


        $adjustment = new Adjustment();
        $adjustment->setAddDate($dateAdjustment);
        $adjustment->setRecorder($user);
        $entityManager->persist($adjustment);

        $cartProducts = $this->cartService->getFullCartAdjustment();

        foreach ($cartProducts as $item){
            $productAdjustStocks = $this->cartService->getProductStocks($item);
            $productAdjust = new ProductAdjust();
            $productAdjust->setQty($item['qtyAdjust']);
            $productAdjust->setNewQty($item['qty']);
            $productAdjust->setQtyBeforeAdjust($item['qtyStock']);
            $productAdjust->setProduct($item['product']);


            $productAdjust->setAdjustment($adjustment);
            foreach ($productAdjustStocks as $productAdjustStock){
                $entityManager->persist($productAdjustStock);
                $productAdjust->addProductAdjustStock($productAdjustStock);
            }
            $productAdjust->setUnitPrice($productAdjustStocks[0]->getUnitPrice());
            $entityManager->persist($productAdjust);
        }

        $entityManager->flush();

        // clear a cart
        $this->cartService->removeAll();

        $model['products'] = $this->getProductList();
        $model['cart'] = $this->getInfoCart();

        return $this->json($model,200);

    }

    /**
     * @Route("/cart/adjustment/get", name="rest_cart_adjustment_get")
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
        $model['items'] = $this->cartService->getFullCartAdjustment();
        return $this->render('partials/cartadjustment.html.twig',$model);
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
