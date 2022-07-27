<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Dto\ProductStockDto;
use App\Entity\Setting;
use App\Entity\Stock;
use App\Entity\ProductStock;
use App\Repository\CustomerProductPriceRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductSaleRepository;
use App\Repository\ProductStockRepository;
use App\Repository\SupplierRepository;
use App\Repository\TaxRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use App\Service\ProductService;
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
class RestProductStockController extends AbstractController
{
    /**
     * @var Setting
     */
    private $setting;

    /**
     * ProductionController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->setting = $requestStack->getSession()->get('setting');
    }

    /**
     * @Route("/productStock", name="rest_product_stock",methods={"GET","POST"})
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param ProductSaleRepository $productSaleRepository
     * @param CustomerRepository $customerRepository
     * @param UserRepository $userRepository
     * @param ProductStockRepository $productStockRepository
     * @return Response
     * @throws Exception
     */
    public function getProductStocks(Request $request,
                                     ProductRepository $productRepository,
                                     ProductSaleRepository $productSaleRepository,
                                     CustomerRepository $customerRepository,
                                     UserRepository $userRepository,
                                     ProductStockRepository $productStockRepository): Response
    {

        $startDate = ($request->get('start')=== 'null')?new DateTime():new DateTime($request->get('start'));
        $endDate = ($request->get('end')=== 'null')?new DateTime():new DateTime($request->get('end'));

        $model['product'] = $productRepository->find((int) $request->get('product'));
        $employee = $userRepository->find((int) $request->get('employee'));
        $customer = $customerRepository->find((int) $request->get('customer'));

        $productStocks = $productSaleRepository
            ->findProductSaleByGroup($startDate,$endDate,$model['product'],$employee,$customer);

        $model['productStocks'] = array_map(function($productStock) use ($productStockRepository){
            $pst = $productStockRepository->find($productStock['id']);
            $supplier = $pst->getStock()->getSupplier();
            $str = '';
            if ($supplier !== null){
                $str .= $supplier->getName();
            }else{
                $str .= '//';
            }
            $str .=' | '.$pst->getStockDate()->format($this->setting->getDateMedium());
            return [
                'batch' => $str,
                'stockId' => $pst->getStock()->getId(),
                'batchId' => $pst->getBatchId(),
                'price' => $pst->getUnitPrice(),
                'buyPrice' => $pst->getUnitPrice(),
                'sellPrice' => $productStock['sellPrice'],
                'profit' => $productStock['profit'],
                'qty' => $productStock['qty']];
        },$productStocks);

        $data = $this->render('report/modalProductStock.html.twig',$model);
        return $this->json($data);
    }

    /**
     * @Route("/productStocks", name="rest_productStock",methods={"POST"})
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param CustomerProductPriceRepository $customerProductPriceRepository
     * @param SessionInterface $session
     * @param CustomerRepository $customerRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function getProductStock(Request $request,
                                    ProductRepository $productRepository,
                                    CustomerProductPriceRepository $customerProductPriceRepository,
                                    SessionInterface $session,
                                    CustomerRepository $customerRepository,
                                    ProductService $productService): Response
    {

        $product = $productService
            ->countStock($productRepository->find((int) $request->get('id')));

        $cartCustomer = $session->get('customer');
        $customerId = $cartCustomer ?? null;

        $sellPrice = $product->getWholePrice();
        if (($customerId !== null) && $this->setting->getWithWholeSale()
            && count($product->getCustomerProductPrices()) > 0) {

            $customer = $customerRepository->find((int)$customerId);

            $customerProductPrice = $customerProductPriceRepository
                ->findOneBy(['product'=>$product,'customer' => $customer]);
            if ($customerProductPrice !== null){
                $sellPrice = $customerProductPrice->getPrice();
            }
        }

        $model['sellPrice'] = $sellPrice;

        $productStocks = $productService
            ->getProductStockDispoByProduct($product);

        $model['productStocks'] = [];
        foreach ($productStocks as $productStock){
            $model['productStocks'][] =
                ProductStockDto::createFromEntity($productStock,true,true);
        }

        return $this->json($model);
    }

    /**
     * @Route("/productStock/changeQty", name="rest_productStock_changeQty",methods={"POST"})
     * @param Request $request
     * @param ProductStockRepository $productStockRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function changeQtyProductStock(Request $request,
                                          ProductStockRepository $productStockRepository,
                                          EntityManagerInterface $entityManager): Response
    {

        $productStock = $productStockRepository->find((int) $request->get('id'));

        if (null !== $productStock){
            $model['stock'] = $productStock->getStock()->getId();
            $model['locale'] = $request->get('_locale');

            $productStock->setQty((int) $request->get('qty'));
            $entityManager->persist($productStock);
            $entityManager->flush();
        }else{
            $model['stock'] = null;
        }

        return $this->json($model);
    }

    /**
     * @Route("/productStock/changeExpirationDate", name="rest_productStock_changeExpirationDate",methods={"POST"})
     * @param Request $request
     * @param ProductStockRepository $productStockRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function changeExpirationDateProductStock(Request $request,
                                                     ProductStockRepository $productStockRepository,
                                                     EntityManagerInterface $entityManager): Response
    {

        $productStock = $productStockRepository
            ->find((int) $request->get('id'));

        if (null !== $productStock){
            $model['stock'] = $productStock->getStock()->getId();
            $model['locale'] = $request->get('_locale');

            $expirationDate = ($request->get('expirationDate')=== 'null')?null:new DateTime($request->get('expirationDate'));
            $productStock->setExpirationDate($expirationDate);
            $entityManager->persist($productStock);
            $entityManager->flush();
        }else{
            $model['stock'] = null;
        }

        return $this->json($model);
    }

    /**
     * @Route("/productStock/changeUnitPrice", name="rest_productStock_changeUnitPrice",methods={"POST"})
     * @param Request $request
     * @param ProductStockRepository $productStockRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function changeUnitPriceProductStock(Request $request,
                                                ProductStockRepository $productStockRepository,
                                                EntityManagerInterface $entityManager): Response
    {

        $productStock = $productStockRepository
            ->find((int) $request->get('id'));

        if (null !== $productStock){
            $model['stock'] = $productStock->getStock()->getId();
            $model['locale'] = $request->get('_locale');

            $productStock->setUnitPrice((float) $request->get('unitPrice'));
            $entityManager->persist($productStock);
            $entityManager->flush();
        }else{
            $model['stock'] = null;
        }

        return $this->json($model);
    }
}
