<?php

namespace App\Controller;

use App\Dto\PermissionDto;
use App\Dto\ProductStockDto;
use App\Dto\ProductStockSaleDto;
use App\Entity\Permission;
use App\Entity\ProductStock;
use App\Entity\Setting;
use App\Repository\CustomerRepository;
use App\Repository\NoticeBoardEmployeeRepository;
use App\Repository\PermissionRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductSaleRepository;
use App\Repository\ProductSaleReturnRepository;
use App\Repository\ProductStockReturnRepository;
use App\Repository\SalePaymentRepository;
use App\Repository\SaleRepository;
use App\Repository\StockPaymentRepository;
use App\Repository\StoreRepository;
use App\Repository\ProductStockRepository;
use App\Repository\StockRepository;
use App\Repository\SupplierRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\CartWholeSalerService;
use App\Service\ProductService;
use App\Service\SendMailer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/api")
 */
class RestGlobalController extends AbstractController
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
     * @Route("/product", name="rest_product",methods={"GET"})
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function getProducts(ProductRepository $productRepository,
                                    ProductService $productService): Response
    {

        $products = $productService
            ->countStocks($productRepository->findAll());


        return $this->json($products,200,[],['groups'=>['product:read']]);
    }

    /**
     * @Route("/shortcut", name="rest_shortcut",methods={"GET"})
     * @param PermissionRepository $permissionRepository
     * @return Response
     */
    public function getShortcuts(PermissionRepository $permissionRepository): Response
    {

        $permissions = $permissionRepository->findBy([],['code' => 'DESC']);
        $shortcuts = array_filter($permissions,
            static function(Permission $permission){
                return ($permission->getShortcut() !== null && $permission->getShortcut() !== '');
            });

        $shortcuts = array_map(function(PermissionDto $permissionDto){
            $permissionDto->link = $this->generateUrl($permissionDto->link);
            return $permissionDto;
        },array_map(static function(Permission $permission){
            return PermissionDto::createFromEntity($permission);
        },$shortcuts));

        sort($shortcuts);

        return $this->json($shortcuts,200);
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
        return $this->json($data,200);
    }

    /**
     * @Route("/productStock/incorrect", name="rest_product_stock_incorrect",methods={"GET","POST"})
     * @param ProductService $productService
     * @param ProductStockRepository $productStockRepository
     * @return Response
     */
    public function getProductStockIncorrects(ProductService $productService,
                                     ProductStockRepository $productStockRepository): Response
    {

        $productStocks = array_filter(array_map(static function (ProductStock $productStock) use($productService){
            return ProductStockDto::createFromEntity(
                $productService->countQtyRemaining($productStock),true,true);
        },$productStockRepository->findAll()),
            static function (ProductStockDto $productStockDto){
            return 0 > $productStockDto->qtyRemaining;
        });

        return $this->json($productStocks,200);
    }


    /**
     * @Route("/saleReturn", name="rest_sale_return",methods={"GET","POST"})
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param ProductSaleReturnRepository $productSaleReturnRepository
     * @return Response
     * @throws Exception
     */
    public function getSaleReturns(Request $request,
                                     ProductRepository $productRepository,
                                     ProductSaleReturnRepository $productSaleReturnRepository): Response
    {

        $startDate = ($request->get('start')=== 'null')?new DateTime():new DateTime($request->get('start'));
        $endDate = ($request->get('end')=== 'null')?new DateTime():new DateTime($request->get('end'));

        $model['product'] = $productRepository->find((int) $request->get('product'));

        $model['saleReturns'] = $productSaleReturnRepository
            ->findProductSaleReturnByGroup($startDate,$endDate,$model['product']);

        $data = $this->render('report/modalSaleReturn.html.twig',$model);
        return $this->json($data,200);
    }


    /**
     * @Route("/stockReturn", name="rest_stock_return",methods={"GET","POST"})
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param ProductStockReturnRepository $productStockReturnRepository
     * @return Response
     * @throws Exception
     */
    public function getStockReturns(Request $request,
                                   ProductRepository $productRepository,
                                   ProductStockReturnRepository $productStockReturnRepository): Response
    {

        $startDate = ($request->get('start')=== 'null')?new DateTime():new DateTime($request->get('start'));
        $endDate = ($request->get('end')=== 'null')?new DateTime():new DateTime($request->get('end'));

        $model['product'] = $productRepository->find((int) $request->get('product'));

        $model['stockReturns'] = $productStockReturnRepository
            ->findProductStockReturnByGroup($startDate,$endDate,$model['product']);

        $data = $this->render('report/modalStockReturn.html.twig',$model);
        return $this->json($data,200);
    }

    /**
     * @Route("/notice/seen", name="rest_notice_seen", methods={"GET","POST"})
     * @param Request $request
     * @param NoticeBoardEmployeeRepository $noticeBoardEmployeeRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function noticeSeen(Request $request,
                               NoticeBoardEmployeeRepository $noticeBoardEmployeeRepository,
                               EntityManagerInterface $entityManager): Response
    {

        $noticeBoardEmployee = $noticeBoardEmployeeRepository
            ->find((int) $request->get('id'));

        $noticeBoardEmployee->setSeen(true);

        $entityManager->persist($noticeBoardEmployee);
        $entityManager->flush();

        return $this->json(true,200);
    }

    /**
     * @Route("/productStockSales", name="rest_productStockSale",methods={"POST"})
     * @param Request $request
     * @param ProductSaleRepository $productSaleRepository
     * @return Response
     */
    public function getProductStockSale(Request $request,
                                    ProductSaleRepository $productSaleRepository): Response
    {

        $productSale = $productSaleRepository->find((int) $request->get('id'));

        if ($productSale === null){
            return $this->json(null,200);
        }


        $model['productStockSales'] = [];
        foreach ($productSale->getProductStockSales() as $productStockSale){
            $model['productStockSales'][] =
                ProductStockSaleDto::createFromEntity($productStockSale,true);
        }

        return $this->json($model,200);
    }

    /**
     * @Route("/customer", name="rest_customer",methods={"POST"})
     * @param Request $request
     * @param CartWholeSalerService $cartWholeSalerService
     * @return Response
     */
    public function getCustomer(Request $request,
                                CartWholeSalerService $cartWholeSalerService): Response
    {

        $result = $cartWholeSalerService
            ->selectWholeSaler((int) $request->get('id'));


        return $this->json($result,200);
    }

    /**
     * @Route("/sale/copy", name="rest_sale_copy")
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param CartService $cartService
     * @return Response
     * @throws Exception
     */
    public function copySale(Request $request,
                         SaleRepository $saleRepository,
                         CartService $cartService): Response
    {
        $sale = $saleRepository->find((int) $request->get('saleId'));

        $copy = false;
        if (($sale !== null) && !$this->setting->getWithGuiSale()) {
            foreach ($sale->getProductSales() as $productSale){
                $cartService->changeQty($productSale->getProduct()->getId(),
                    $productSale->getQty());
            }
            $copy = true;
        }

        return $this->json($copy,200);
    }

    /**
     * @Route("/product/count", name="rest_product_count")
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function productCount(ProductRepository $productRepository): Response
    {
        $model['count'] = count($productRepository->findAll());
        return $this->json($model,200,[]);
    }


    /**
     * @Route("/header/count", name="rest_header_count")
     * @param ProductService $productService
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function headerCount(ProductService $productService,
                                ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        $model['stockAlertCount'] = count($productService->getProductByStockAlert($products));
        $model['outOfStockCount'] = count($productService->getProductByOutOfStock($products));
        $model['stockOutOfDateCount'] = count($productService->getProductStockOutdated(false));
        $model['stockExpiryDateCount'] = count($productService
            ->getProductStockNearExpirationDate($this->setting->getDaysBeforeExpiration()));
        return $this->json($model,200,[]);
    }


    /**
     * @Route("/stock/sendByMail", name="rest_stock_byMail", methods={"POST","GET"})
     * @param Request $request
     * @param Pdf $pdf
     * @param SendMailer $sendMailer
     * @param StockRepository $stockRepository
     * @param StoreRepository $storeRepository
     * @return Response
     */
    public function sendByMail(Request $request,
                               Pdf $pdf,
                               SendMailer $sendMailer,
                               StockRepository $stockRepository,
                               StoreRepository $storeRepository): Response
    {

        $model['stock'] = $stockRepository->find((int) $request->get('id'));

        if ($model['stock']->getSupplier() === null
            || empty($model['stock']->getSupplier()->getEmail())){
            return $this->json(false,200);
        }

        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }
   
        $html = $this->renderView('pdf/order.html.twig',$model);

        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('page-height', 297);
        $pdf->setOption('page-width', 210);
        $file = $pdf->getOutputFromHtml($html);

        $sended = $sendMailer
            ->sendOrder($model['stock']->getSupplier(),$file);
        return $this->json($sended,200);
    }

    /**
     * @Route("/product/modal", name="rest_product_modal_delete", methods={"POST"})
     * @param ProductRepository $productRepository
     * @param Request $request
     * @return Response
     */
    public function deleteModal(ProductRepository $productRepository, Request $request): Response
    {
        $model['product'] = $productRepository->find((int) $request->get('id'));
        $data = $this->render('product/modalProduct.html.twig',$model);
        return $this->json($data,200);
    }

    /**
     * @Route("/customer/saleDebt/modal", name="rest_customer_saleDebt_modal", methods={"POST"})
     * @param CustomerRepository $customerRepository
     * @param Request $request
     * @return Response
     */
    public function saleDebtModal(CustomerRepository $customerRepository, Request $request): Response
    {
        $model['customer'] = $customerRepository->find((int) $request->get('id'));
        $data = $this->render('customer/modalSaleDebt.html.twig',$model);
        return $this->json($data,200);
    }

    /**
     * @Route("/customer/salePayment/modal", name="rest_customer_salePayment_modal", methods={"POST"})
     * @param CustomerRepository $customerRepository
     * @param SalePaymentRepository $salePaymentRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function salePaymentModal(CustomerRepository $customerRepository,
                                     SalePaymentRepository $salePaymentRepository,
                                     Request $request): Response
    {
        $model['customer'] = $customerRepository->find((int) $request->get('customer'));

        $model['date'] = $request->get('date') ?? new DateTime();

        if (!$model['date'] instanceof DateTime){
            $model['date'] = new DateTime($model['date']);
        }

        $model['salePayments'] = $salePaymentRepository
            ->findByPeriodDate($model['date'],$model['date'],null,$model['customer']);

        $data = $this->render('sale/modalSalePayment.html.twig',$model);
        return $this->json($data,200);
    }

    /**
     * @Route("/supplier/stockPayment/modal", name="rest_supplier_stockPayment_modal", methods={"POST"})
     * @param SupplierRepository $supplierRepository
     * @param StockPaymentRepository $stockPaymentRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function stockPaymentModal(SupplierRepository $supplierRepository,
                                     StockPaymentRepository $stockPaymentRepository,
                                     Request $request): Response
    {
        $model['supplier'] = $supplierRepository->find((int) $request->get('supplier'));

        $model['date'] = $request->get('date') ?? new DateTime();

        if (!$model['date'] instanceof DateTime){
            $model['date'] = new DateTime($model['date']);
        }

        $model['stockPayments'] = $stockPaymentRepository
            ->findStockPaymentByPeriod($model['date'],$model['date'],null,$model['supplier']);

        $data = $this->render('stock/modalStockPayment.html.twig',$model);
        return $this->json($data,200);
    }


    /**
     * @Route("/product/modal/listSubstitute", name="rest_product_modal_substitute", methods={"POST"})
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function listSubstituteModal(ProductRepository $productRepository,
                                     ProductService $productService,
                                     Request $request): Response
    {
        $model['product'] = $productRepository->find((int) $request->get('id'));

        $withStock = $request->get('withStock')?? false;
        $model['substitutes'] =
            $productService->countStocks($model['product']->getSubstitutes());
        if ($withStock){
            $data = $this->render('sale/modalSubstitute.html.twig',$model);
        }else{
            $data = $this->render('product/modalSubstitute.html.twig',$model);
        }
        return $this->json($data,200);
    }


}
