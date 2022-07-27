<?php

namespace App\Controller;


use App\Entity\Setting;
use App\Entity\Stock;
use App\Entity\ProductStock;
use App\Entity\StockFee;
use App\Repository\PaymentMethodRepository;
use App\Repository\SupplierRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\ProductStockRepository;
use App\Repository\StockRepository;
use App\Service\OrderService;
use App\Service\ProductService;
use App\Service\StockService;
use App\Util\GlobalConstant;
use App\Util\RandomUtil;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class StockController extends AbstractController
{

    /**
     * @var Setting
     */
    private $setting;

    /**
     * ExpenseController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->setting = $requestStack->getSession()->get('setting');
    }


    /**
     * @Route("/stock", name="stock_index", methods={"GET","POST"})
     * @param StockRepository $stockRepository
     * @param StockService $stockService
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(StockRepository $stockRepository,
                          StockService $stockService,
                          Request $request): Response
    {
        $intervalDays = (int) $this->setting->getMaxIntervalPeriod();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.sale.index.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $stocks = $stockRepository
            ->findStockByPeriod($model['start'],$model['end']);

        $model['stocks'] = $stockService->getAmounts($stocks);

        //breadcumb
        $model['entity'] = 'controller.stock.index.entity';
        $model['page'] = 'controller.stock.index.page';
        return $this->render('stock/index.html.twig', $model);
    }

    /**
     * @Route("/stock/detail/{id}", name="stock_detail")
     * @param Stock $stock
     * @param ProductService $productService
     * @param ProductRepository $productRepository
     * @param SupplierRepository $supplierRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param StockService $stockService
     * @return Response
     */
    public function detail(Stock $stock,
                           ProductService $productService,
                           ProductRepository $productRepository,
                           SupplierRepository $supplierRepository,
                           PaymentMethodRepository $paymentMethodRepository,
                           StockService $stockService): Response
    {
        $amount = $stockService->getAmount($stock);
        $stock->setAmount($amount);
        $model['stock'] = $stock;
        $model['productStocks'] = array_map(
            static function (ProductStock $productStock) use($productService){
            return $productService->countQtyRemaining($productStock);
        },$stock->getProductStocks()->toArray());

        $model['products'] = $productRepository->findBy(['enabled' => true]);
        $model['paymentMethods'] = $paymentMethodRepository->findBy(['status' => true]);

        $model['suppliers'] = $supplierRepository->findAll();

        //breadcumb
        $model['entity'] = 'controller.stock.detail.entity';
        $model['page'] = 'controller.stock.detail.page';
        return $this->render('stock/detailStock.html.twig', $model);
    }

    /**
     * @Route("/stock/new", name="stock_new")
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function new(ProductRepository $productRepository,
                        ProductService $productService): Response
    {

        $products = $productRepository->findBy(['enabled' => true],['addDate' => 'DESC']);
        $model['products'] = $productService->countStocks($products);

        $model['entity'] = 'controller.stock.new.entity';
        $model['page'] = 'controller.stock.new.page';

        return $this->render('stock/add.html.twig', $model);

    }

    /**
     * @Route("/stock/add", name="stock_add")
     * @param OrderService $orderService
     * @param Request $request
     * @param SupplierRepository $supplierRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(OrderService $orderService,
                        Request $request,
                        SupplierRepository $supplierRepository,
                        EntityManagerInterface $entityManager): Response
    {

        if (empty($orderService->getFullOrder())){
            return $this->redirectToRoute('stock_new');
        }

        $supplier = null;
        if (isset($_GET['supplier']) && $request->get('supplier') !== '0'){
            $supplier = $supplierRepository
                ->find($request->get('supplier'));
        }

        $stock = new Stock();
        $stock->setAmount($orderService->getTotal());
        $stock->setSupplier($supplier);
        $stock->setAddDate(new DateTime());
        $stock->setRecorder($this->getUser());

        $entityManager->persist($stock);

        foreach ($orderService->getFullOrder() as $item){
            $productStock = new ProductStock();
            $productStock->setProduct($item['product']);
            $productStock->setQty($item['qty']);
            $productStock->setStock($stock);
            $productStock->setUnitPrice($item['price']);
            $entityManager->persist($productStock);
        }
        $orderService->removeAll();
        $entityManager->flush();

        $this->addFlash('success',"controller.stock.add.flash.success");

        return $this->redirectToRoute('stock_index');

    }

    /**
     * @Route("/stock/setStatus", name="stock_set_status")
     * @param Request $request
     * @param StockRepository $stockRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function setStatus(Request $request, StockRepository $stockRepository,
                              EntityManagerInterface $entityManager): RedirectResponse
    {

        $stockId = (int) $request->get('stockId');
        $routeToRedirect = $this->redirectToRoute('stock_detail',
            [
                'id' => $stockId
            ]);

        $stock = $stockRepository->find($stockId);
        if (null !== $stock){
            $numInvoice = $request->get('numInvoice');
            if (empty($numInvoice) && $this->setting->getWithStockGenerateNumInvoice()){
                $format = str_replace($this->setting->getDateSeparator(),'',$this->setting->getDateMedium());
                $numInvoice = $stock->getAddDate()->format($format).'/'.$stockId;
            }

            $stock->setNumInvoice($numInvoice);
            $stock->setNumBill($request->get('numBill'));
            $stock->setStatus(true);
            $stock->setDeliveryDate(new DateTime());
            $entityManager->persist($stock);
            $entityManager->flush();
        }

        return $routeToRedirect;
    }

    /**
     * @Route("/stock/print/{id}", name="stock_print")
     * @param Stock $stock
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @return PdfResponse
     * @throws Exception
     */
    public function print(Stock $stock, Pdf $pdf, StoreRepository $storeRepository): PdfResponse
    {

        $model['stock'] = $stock;
        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        $html = $this->renderView('pdf/order.html.twig',$model);

        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('page-height', $this->setting->getReportHeight());
        $pdf->setOption('page-width', $this->setting->getReportWidth());
        $file = $pdf->getOutputFromHtml($html);
        $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
       return new PdfResponse(
           $file,
           $filename,
           'application/pdf',
           'inline'
       );

    }

    /**
     * @Route("/stock/delete/{id}", name="stock_delete")
     * @param Stock $stock
     * @param ProductStockRepository $productStockRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Stock $stock, ProductStockRepository $productStockRepository,
                           EntityManagerInterface $entityManager): RedirectResponse
    {

        $this->denyAccessUnlessGranted('STOCK_DELETE',$stock);
        $productStocks = $productStockRepository->findBy(['stock' => $stock]);
        foreach ($productStocks as $productStock){
            $entityManager->remove($productStock);
        }

        $entityManager->remove($stock);
        $entityManager->flush();
        $this->addFlash('success',"controller.stock.delete.flash.success");
        return $this->redirectToRoute('stock_index');
    }

    /**
     * @Route("/stock/change/numInvoice", name="stock_change_num_invoice")
     * @param Request $request
     * @param StockRepository $stockRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function stockNumInvoice(Request $request,
                                   StockRepository $stockRepository,
                                   EntityManagerInterface $entityManager): RedirectResponse
    {
        $stockId = (int) $request->get('stockId');
        $stock = $stockRepository->find($stockId);

        if ($stock !== null){

            $numInvoice = (empty($request->get('numInvoice')))?null:$request->get('numInvoice');

            if ($numInvoice !== null){
                $stockByNumInvoice = $stockRepository->findOneBy(['numInvoice'=>$numInvoice]);
                if ($stockByNumInvoice !== null) {
                    $this->addFlash('danger',"controller.stock.changeNumInvoice.flash.danger");
                    return $this->redirectToRoute('stock_detail',['id' => $stockId]);
                }
            }

            $stock->setNumInvoice($numInvoice);

            $entityManager->persist($stock);
            $entityManager->flush();
        }

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }

    /**
     * @Route("/stock/change/date", name="stock_change_date")
     * @param Request $request
     * @param StockRepository $stockRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function stockChangeDate(Request $request,
                                   StockRepository $stockRepository,
                                   EntityManagerInterface $entityManager): RedirectResponse
    {

        $date = $request->get('date') ?? new DateTime();
        $date = (!$date instanceof DateTime)? new DateTime($date) : $date;

        $stockId = (int) $request->get('stockId');
        $stock = $stockRepository->find($stockId);
        if ($stock !== null){
            $stock->setDeliveryDate($date);

            $entityManager->persist($stock);
            $entityManager->flush();
        }

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }

    /**
     * @Route("/stock/change/supplier", name="stock_change_supplier")
     * @param Request $request
     * @param SupplierRepository $supplierRepository
     * @param StockRepository $stockRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function stockChangeSupplier(Request $request,
                                          SupplierRepository $supplierRepository,
                                          StockRepository $stockRepository,
                                          EntityManagerInterface $entityManager): RedirectResponse
    {

        $supplier = $supplierRepository->find((int) $request->get('supplier'));

        $stockId = (int) $request->get('stockId');
        $stock = $stockRepository->find($stockId);
        if ($stock !== null && $stock->getSupplier() !== $supplier){
            $stock->setSupplier($supplier);

            $entityManager->persist($stock);
            $entityManager->flush();
        }

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }

    /**
     * @Route("/stock/fee/add", name="stock_fee_add")
     * @param Request $request
     * @param StockRepository $stockRepository
     * @param StockService $stockService
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function productFeeAdd(Request $request,
                                  StockRepository $stockRepository,
                                  StockService $stockService,
                                  EntityManagerInterface $entityManager): RedirectResponse
    {

        $amountFee = (float) $request->get('amount');
        $stockId = (int) $request->get('stockId');
        if ($amountFee > 0){
            $stock = $stockRepository->find($stockId);


            $stockFee = new StockFee();
            $stockFee->setName($request->get('name'));
            $stockFee->setAmount($amountFee);
            $stockFee->setStock($stock);

            $entityManager->persist($stockFee);
            $entityManager->flush();

            $amountStock = $stockService->getAmount($stock);
            $stock->setAmount($amountStock);
            $entityManager->persist($stock);


            $entityManager->flush();

        }else{
            $this->addFlash('danger',"controller.stock.fee.flash.danger");
        }

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }


    /**
     * @Route("/stock/fee/delete/{id}", name="stock_fee_delete")
     * @param StockFee $stockFee
     * @param StockService $stockService
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function stockFeeDelete(StockFee $stockFee,
                                        StockService $stockService,
                                        EntityManagerInterface $entityManager): RedirectResponse
    {

        $stock = $stockFee->getStock();
        $stockId = $stock->getId();

        $entityManager->remove($stockFee);
        $entityManager->flush();

        $amountStock = $stockService->getAmount($stock);
        $stock->setAmount($amountStock);
        $entityManager->persist($stock);
        $entityManager->flush();

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }
}
