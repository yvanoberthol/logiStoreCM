<?php

namespace App\Controller;

use App\Entity\Encashment;
use App\Entity\Loss;
use App\Entity\Permission;
use App\Entity\Production;
use App\Entity\ProductionStorage;
use App\Entity\ProductSaleReturn;
use App\Entity\ProductStock;
use App\Entity\SalaryPayment;
use App\Entity\Sale;

use App\Entity\Setting;
use App\Entity\Stock;
use App\Entity\Expense;
use App\Entity\Supply;
use App\Entity\Transaction;
use App\Entity\User;
use App\Extension\AppExtension;
use App\Repository\AdjustmentRepository;
use App\Repository\BankRepository;
use App\Repository\CustomerRepository;
use App\Repository\EncashmentRepository;
use App\Repository\ExpenseTypeRepository;
use App\Repository\LossRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductionRawRepository;
use App\Repository\ProductionRepository;
use App\Repository\ProductionStorageRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductSaleRepository;
use App\Repository\ProductStockRepository;
use App\Repository\StoreRepository;
use App\Repository\SaleRepository;
use App\Repository\StockRepository;
use App\Repository\ExpenseRepository;
use App\Repository\SupplierRepository;
use App\Repository\SupplyRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\CustomerService;
use App\Service\EmployeeService;
use App\Service\EncashmentService;
use App\Service\ProductionStorageService;
use App\Service\ProductionStorageSupplyService;
use App\Service\RawMaterialService;
use App\Util\CustomerTypeConstant;
use App\Util\GlobalConstant;
use App\Util\ModuleConstant;
use App\Util\RandomUtil;
use DateTime;
use Exception;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ReportController extends AbstractController
{
    /**
     * @var AppExtension
     */
    private $appExtension;
    /**
     * @var Setting
     */
    private $setting;

    /**
     * @param AppExtension $appExtension
     * @param RequestStack $requestStack
     */
    public function __construct(AppExtension $appExtension, RequestStack $requestStack)
    {
        $this->appExtension = $appExtension;
        $this->setting = $requestStack->getSession()->get('setting');
    }


    /**
     * @Route("/report/product", name="report_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param ProductSaleRepository $productSaleRepository
     * @param LossRepository $lossRepository
     * @param ProductStockRepository $productStockRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function product(Request $request,
                             Pdf $pdf,
                             StoreRepository $storeRepository,
                             ProductSaleRepository $productSaleRepository,
                             LossRepository $lossRepository,
                             ProductStockRepository $productStockRepository,
                             ProductRepository $productRepository): Response
    {

        $model['store'] = $storeRepository->get();
        $model['products'] = $productRepository->findAll();

        $intervalDays = $this->setting->getMaxIntervalPeriod();
        $today = (new DateTime())->format('Y-m-d');
        $model['start'] = $request->get('start') ?? new DateTime($today.' 00:00');
        $model['end'] = $request->get('end') ?? new DateTime($today.' 23:59');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = $today;
            $model['end'] = $today;
            $this->addFlash('danger',"controller.report.sale.flash.danger");
        }

        if ($request->isMethod('POST')){
            $product = $productRepository
                ->find((int) $request->get('product'));

            if ($product !== null){
                $model['product'] = $product;

                if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
                    $model['start'] = new DateTime($model['start']);
                    $model['end'] = new DateTime($model['end']);
                }

                $model['productSales'] = $productSaleRepository
                    ->findProductSaleByGroupPeriod($model['start'],$model['end'],$product);
                $model['losses'] = $lossRepository
                    ->findLossByPeriod($model['start'],$model['end'],$product);
                $model['productStocks'] = $productStockRepository
                    ->findProductStockByPeriod($model['start'],$model['end'],$product);


                if (!empty($model['productSales'])){
                    $model['profitAmountSold'] = array_sum(array_map(static function($sale){
                        return $sale['profit'];
                    },$model['productSales']));
                    $model['totalAmountSold'] = array_sum(array_map(static function($sale){
                        return $sale['subtotal'];
                    },$model['productSales']));
                    $model['qtySold'] = array_sum(array_map(static function($sale){
                        return $sale['qty'];
                    },$model['productSales']));
                }


                if (!empty($model['losses'])) {
                    $model['qtyLost'] = array_sum(array_map(static function (Loss $loss) {
                        return $loss->getQty();
                    }, $model['losses']));
                    $model['totalAmountLost'] = array_sum(array_map(static function (Loss $loss) {
                        return  $loss->getAmount();
                    }, $model['losses']));
                }

                if (!empty($model['productStocks'])) {
                    $model['qtyStock'] = array_sum(array_map(static function (ProductStock $stock) {
                        return $stock->getQty();
                    }, $model['productStocks']));
                    $model['totalAmountStock'] = array_sum(array_map(static function (ProductStock $stock) {
                        return $stock->getSubtotal();
                    }, $model['productStocks']));
                }

                if ($request->get('print')){

                    $html = $this->renderView('pdf/report/product.html.twig',$model);

                    $pdf->setOption('enable-local-file-access', true);
                    $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                    $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                    $file = $pdf->getOutputFromHtml($html);
                    $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                    return new PdfResponse(
                        $file,
                        $filename,
                        'application/pdf',
                        'inline'
                    );
                }
            }

        }

        //breadcumb
        $model['entity'] = 'controller.report.product.entity';
        $model['page'] = 'controller.report.product.page';
        return $this->render('report/product.html.twig', $model);
    }

    /**
     * @Route("/report/expense", name="report_expense", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param ExpenseRepository $expenseRepository
     * @param ExpenseTypeRepository $expenseTypeRepository
     * @return Response
     * @throws Exception
     */
    public function expense(Request $request,
                                Pdf $pdf,
                                StoreRepository $storeRepository,
                                ExpenseRepository $expenseRepository,
                                ExpenseTypeRepository $expenseTypeRepository): Response
    {

        /*if ($_ENV['WITH_ACCOUNTING']==='false'){
            throw new NotFoundHttpException("this ressource don't exists");
        }*/

        $model['store'] = $storeRepository->get();
        $model['types'] = $expenseTypeRepository->findBy(['status' => true]);

        $intervalDays = $this->setting->getMaxIntervalPeriod();
        $today = new DateTime();
        $model['start'] = $request->get('start') ?? $today;
        $model['end'] = $request->get('end') ?? $today;

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = $today;
            $model['end'] = $today;
            $this->addFlash('danger',"controller.report.sale.flash.danger");
        }

        if ($request->isMethod('POST')){
            $type = $expenseTypeRepository
                ->find((int) $request->get('type'));

            $model['type'] = $type;

            if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
                $model['start'] = new DateTime($model['start']);
                $model['end'] = new DateTime($model['end']);
            }

            if ($type !== null){
                $model['expenses'] = $expenseRepository
                    ->findExpenseByPeriod($model['start'],$model['end'],$type->getId());
            }else{
                $model['expenses'] = $expenseRepository
                    ->findExpenseByPeriod($model['start'],$model['end']);
            }

            $model['totalAmount'] = 0;
            if (!empty($model['expenses'])){
                $model['totalAmount'] = array_sum(
                    array_map(static function(Expense $expense){
                        return $expense->getAmount();
                    },$model['expenses']));
            }

            if ($request->get('print')){

                $html = $this->renderView('pdf/report/expense.html.twig',$model);

                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                $file = $pdf->getOutputFromHtml($html);
                $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                return new PdfResponse(
                    $file,
                    $filename,
                    'application/pdf',
                    'inline'
                );
            }

        }

        //breadcumb
        $model['entity'] = 'controller.report.expense.entity';
        $model['page'] = 'controller.report.expense.page';
        return $this->render('report/expense.html.twig', $model);
    }

    /**
     * @Route("/report/transaction", name="report_transaction", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param TransactionRepository $transactionRepository
     * @param BankRepository $bankRepository
     * @return Response
     * @throws Exception
     */
    public function transaction(Request $request,
                                Pdf $pdf,
                                StoreRepository $storeRepository,
                                TransactionRepository $transactionRepository,
                                BankRepository $bankRepository): Response
    {

        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['store'] = $storeRepository->get();
        $model['banks'] = $bankRepository->findAll();

        $intervalDays = $this->setting->getMaxIntervalPeriod();
        $today = new DateTime();
        $model['start'] = $request->get('start') ?? $today;
        $model['end'] = $request->get('end') ?? $today;

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = $today;
            $model['end'] = $today;
            $this->addFlash('danger',"controller.report.sale.flash.danger");
        }

        if ($request->isMethod('POST')){
            $bank = $bankRepository
                ->find((int) $request->get('bank'));

            if ($bank !== null){
                $model['bank'] = $bank;

                if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
                    $model['start'] = new DateTime($model['start']);
                    $model['end'] = new DateTime($model['end']);
                }

                $model['transactions'] = $transactionRepository
                    ->findTransactionByPeriod($model['start'],$model['end'],$bank->getId());

                $model['totalDebit'] = 0;
                $model['totalCredit'] = 0;
                if (!empty($model['transactions'])){
                    $model['totalDebit'] = array_sum(
                        array_map(static function(Transaction $transaction){
                        return $transaction->getTotalAmount();
                        },array_filter($model['transactions'], static function(Transaction $transaction){
                            return $transaction->getType() === '0';
                        })
                        )
                    );

                    $model['totalCredit'] = array_sum(
                        array_map(static function(Transaction $transaction){
                            return $transaction->getTotalAmount();
                        },array_filter($model['transactions'], static function(Transaction $transaction){
                                return $transaction->getType() === '1';
                            })
                        )
                    );
                }

                if ($request->get('print')){

                    $html = $this->renderView('pdf/report/transaction.html.twig',$model);

                    $pdf->setOption('enable-local-file-access', true);
                    $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                    $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                    $file = $pdf->getOutputFromHtml($html);
                    $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                    return new PdfResponse(
                        $file,
                        $filename,
                        'application/pdf',
                        'inline'
                    );
                }
            }

        }

        //breadcumb
        $model['entity'] = 'controller.report.transaction.entity';
        $model['page'] = 'controller.report.transaction.page';
        return $this->render('report/transaction.html.twig', $model);
    }

    /**
     * @Route("/report/activity", name="report_activity", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function activity(Request $request,
                         Pdf $pdf,
                         StoreRepository $storeRepository,
                         SaleRepository $saleRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $today = (new DateTime())->format('Y-m-d');
        $model['start'] = $request->get('start') ?? new DateTime($today.' 00:00');
        $model['end'] = $request->get('end') ?? new DateTime($today.' 23:59');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.report.sale.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['sales'] = $saleRepository
            ->groupByPeriodDate($model['start'],$model['end']);

        $model['totalAmount'] = array_sum(array_map(static function($sale){
            return $sale['amount'];
        },$model['sales']));
        $model['profitAmount'] = array_sum(array_map(static function($sale){
            return $sale['profit'];
        },$model['sales']));

        $model['nbSales'] = array_sum(array_map(static function($sale){
            return $sale['nbSales'];
        },$model['sales']));

        if ($request->isMethod('POST') && $request->get('print')) {

            $html = $this->renderView('pdf/report/activity.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.activity.entity';
        $model['page'] = 'controller.report.activity.page';
        return $this->render('report/activity.html.twig', $model);
    }


    /**
     * @Route("/report/sale", name="report_sale", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function sale(Request $request,
                         Pdf $pdf,
                         StoreRepository $storeRepository,
                         SaleRepository $saleRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $today = (new DateTime())->format('Y-m-d');
        $model['start'] = $request->get('start') ?? new DateTime($today.' 00:00');
        $model['end'] = $request->get('end') ?? new DateTime($today.' 23:59');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.report.sale.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['sales'] = $saleRepository
            ->findSaleByPeriod($model['start'],$model['end']);

        $model['totalAmount'] = array_sum(array_map(static function(Sale $sale){
            return $sale->getAmountTotalReceived();
        },$model['sales']));
        $model['profitAmount'] = array_sum(array_map(static function(Sale $sale){
            return $sale->getProfit();
        },$model['sales']));

        if ($request->isMethod('POST') && $request->get('print')) {

            $html = $this->renderView('pdf/report/sale.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.sale.entity';
        $model['page'] = 'controller.report.sale.page';
        return $this->render('report/sale.html.twig', $model);
    }

    /**
     * @Route("/report/loss", name="report_loss", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param LossRepository $lossRepository
     * @return Response
     * @throws Exception
     */
    public function loss(Request $request,
                         Pdf $pdf,
                         StoreRepository $storeRepository,
                         LossRepository $lossRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.report.loss.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['losses'] = $lossRepository
            ->findLossByPeriod($model['start'],$model['end']);

        $model['totalAmount'] = array_sum(array_map(static function(Loss $loss){
            return $loss->getAmount();
        },$model['losses']));
        $model['totalQty'] = array_sum(array_map(static function(Loss $loss){
            return $loss->getQty();
        },$model['losses']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/loss.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.loss.entity';
        $model['page'] = 'controller.report.loss.page';
        return $this->render('report/loss.html.twig', $model);
    }

    /**
     * @Route("/report/stock", name="report_stock", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param StockRepository $stockRepository
     * @return Response
     * @throws Exception
     */
    public function stock(Request $request,
                          Pdf $pdf,
                          StoreRepository $storeRepository,
                          StockRepository $stockRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.report.stock.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['stocks'] = $stockRepository
            ->findStockByPeriod($model['start'],$model['end']);

        $model['totalAmount'] = array_sum(array_map(static function(Stock $stock){
            return $stock->getAmount();
        },$model['stocks']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/stock.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.stock.entity';
        $model['page'] = 'controller.report.stock.page';
        return $this->render('report/stock.html.twig', $model);
    }

    /**
     * @Route("/report/productCategory", name="report_product_category", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SaleRepository $saleRepository
     * @param ProductCategoryRepository $productCategoryRepository
     * @return Response
     * @throws Exception
     */
    public function productCategory(Request $request,
                          Pdf $pdf,
                          StoreRepository $storeRepository,
                          SaleRepository $saleRepository,
                          ProductCategoryRepository $productCategoryRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['categories'] = $productCategoryRepository
            ->getByPeriodDate($model['start'],$model['end']);

        $model['totalDiscount'] =
            $saleRepository
                ->getDiscountOnPeriod($model['start'],$model['end']);

        $model['totalAmountWithoutDiscount'] = array_sum(array_map(static function($category){
            return $category['amount'];
        },$model['categories']));

        $model['totalAmount'] =
            $model['totalAmountWithoutDiscount']-$model['totalDiscount'];

        $model['totalProfit'] = array_sum(array_map(static function($category){
            return $category['profit'];
        },$model['categories']))-$model['totalDiscount'];

        $model['qtySold'] = array_sum(array_map(static function($category){
            return $category['qtySold'];
        },$model['categories']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/productCategory.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.productCategory.entity';
        $model['page'] = 'controller.report.productCategory.page';
        return $this->render('report/productCategory.html.twig', $model);
    }


    /**
     * @Route("/report/saleByProduct", name="report_sale_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param SaleRepository $saleRepository
     * @param StoreRepository $storeRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function saleByProduct(Request $request,
                          Pdf $pdf,
                                  SaleRepository $saleRepository,
                          StoreRepository $storeRepository,
                          ProductRepository $productRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['products'] = $productRepository
            ->getByPeriodDate($model['start'],$model['end']);

        $model['sales'] = $saleRepository
            ->findSaleByPeriod($model['start'],$model['end']);

        $model['totalDebt'] = array_sum(array_map(static function(Sale $sale){
            return $sale->getAmountDebt();
        },$model['sales']));

        $model['totalDiscount'] = array_sum(array_map(static function(Sale $sale){
            return $sale->getDiscount();
        },$model['sales']));

        $model['totalAmountWithoutDiscount'] = array_sum(array_map(static function($product){
            return $product['amount'];
        },$model['products']));

        $model['totalAmount'] = $model['totalAmountWithoutDiscount']
            - $model['totalDiscount'] - $model['totalDebt'];

        $model['totalProfitWithoutDiscount'] = array_sum(array_map(static function($product){
            return $product['profit'];
        },$model['products']));

        $model['totalProfit'] = $model['totalProfitWithoutDiscount']
            - $model['totalDiscount'] - $model['totalDebt'];

        $model['totalQty'] = array_sum(array_map(static function($product){
            return $product['qtySold'];
        },$model['products']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/saleByProduct.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.saleByProduct.entity';
        $model['page'] = 'controller.report.saleByProduct.page';
        return $this->render('report/saleByProduct.html.twig', $model);
    }

    /**
     * @Route("/report/adjustmentByProduct", name="report_adjustment_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param AdjustmentRepository $adjustmentRepository
     * @param StoreRepository $storeRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function adjustmentByProduct(Request $request,
                                  Pdf $pdf,
                                  AdjustmentRepository $adjustmentRepository,
                                  StoreRepository $storeRepository,
                                  ProductRepository $productRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['products'] = $productRepository
            ->getByAdjustmentPeriodDate($model['start'],$model['end']);

        $model['totalQty'] = array_sum(array_map(static function($product){
            return $product['qtyAdjusted'];
        },$model['products']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/adjustmentByProduct.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.adjustmentByProduct.entity';
        $model['page'] = 'controller.report.adjustmentByProduct.page';
        return $this->render('report/adjustmentByProduct.html.twig', $model);
    }

    /**
     * @Route("/report/productSaleReturn", name="report_sale_product_return", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function productSaleReturn(Request $request,
                                  Pdf $pdf,
                                  StoreRepository $storeRepository,
                                  ProductRepository $productRepository): Response
    {

        if (!$this->setting->getWithSaleReturn()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $products = $productRepository
            ->getSaleReturnByPeriodDate($model['start'],$model['end']);

        $productStockables = $productRepository
            ->getSaleReturnStockableByPeriodDate($model['start'],$model['end']);

        $productRepays = $productRepository
            ->getSaleReturnRepayByPeriodDate($model['start'],$model['end']);

        $model['products'] = [];
        foreach ($products as $product){
            $product['qtyStockable'] = 0;
            foreach ($productStockables as $productStockable){
                if ($product[0]->getId() === $productStockable[0]->getId()){
                    $product['qtyStockable'] = (int) $productStockable['qtyStockable'];
                    break;
                }
            }

            $product['amountRepay'] = 0;
            foreach ($productRepays as $productRepay){
                if ($product[0]->getId() === $productRepay[0]->getId()){
                    $product['amountRepay'] = (int) $productRepay['amount'];
                    break;
                }
            }

            $model['products'][] = $product;
        }

        $model['totalQtyReturn'] = array_sum(array_map(static function($product){
            return $product['qtyReturn'];
        },$model['products']));

        $model['totalQtyStockable'] = array_sum(array_map(static function($product){
            return $product['qtyStockable'];
        },$productStockables));

        $model['totalAmount'] = array_sum(array_map(static function($product){
            return $product['amount'];
        },$model['products']));

        $model['totalAmountRepay'] = array_sum(array_map(static function($product){
            return $product['amount'];
        },$productRepays));


        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/productSaleReturn.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.productSaleReturn.entity';
        $model['page'] = 'controller.report.productSaleReturn.page';
        return $this->render('report/productSaleReturn.html.twig', $model);
    }


    /**
     * @Route("/report/productStockReturn", name="report_stock_product_return", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function productStockReturn(Request $request,
                                      Pdf $pdf,
                                      StoreRepository $storeRepository,
                                      ProductRepository $productRepository): Response
    {

        if (!$this->setting->getWithStockReturn()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $products = $productRepository
            ->getStockReturnByPeriodDate($model['start'],$model['end']);

        $productRepays = $productRepository
            ->getStockReturnRepayByPeriodDate($model['start'],$model['end']);

        $model['products'] = [];
        foreach ($products as $product){
            $product['amountRepay'] = 0;
            foreach ($productRepays as $productRepay){
                if ($product[0]->getId() === $productRepay[0]->getId()){
                    $product['amountRepay'] = (int) $productRepay['amount'];
                    break;
                }
            }

            $model['products'][] = $product;
        }

        $model['totalQtyReturn'] = array_sum(array_map(static function($product){
            return $product['qtyReturn'];
        },$model['products']));

        $model['totalAmount'] = array_sum(array_map(static function($product){
            return $product['amount'];
        },$model['products']));

        $model['totalAmountRepay'] = array_sum(array_map(static function($product){
            return $product['amount'];
        },$productRepays));


        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/productStockReturn.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.productStockReturn.entity';
        $model['page'] = 'controller.report.productStockReturn.page';
        return $this->render('report/productStockReturn.html.twig', $model);
    }

    /**
     * @Route("/report/category/saleByProduct", name="report_category_sale_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param ProductCategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function saleByProductCategory(Request $request,
                                  Pdf $pdf,
                                  StoreRepository $storeRepository,
                                  ProductCategoryRepository $categoryRepository,
                                  ProductRepository $productRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['categories'] = $categoryRepository->findAll();

        if ($request->isMethod('POST')) {
            $model['category'] = $categoryRepository
                ->find((int) $request->get('category'));

            $model['products'] = $productRepository
                ->getByPeriodDate($model['start'],$model['end'],null,null,$model['category']);

            $model['totalAmount'] = array_sum(array_map(static function($product){
                    return $product['amount'];
                },$model['products']));

            $model['totalProfit'] = array_sum(array_map(static function($product){
                    return $product['profit'];
                },$model['products']));

            $model['totalQty'] = array_sum(array_map(static function($product){
                return $product['qtySold'];
            },$model['products']));

            if ($model['category'] !== null && $request->get('print')){
                $html = $this->renderView('pdf/report/saleByProductCategory.html.twig',$model);

                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                $file = $pdf->getOutputFromHtml($html);
                $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                return new PdfResponse(
                    $file,
                    $filename,
                    'application/pdf',
                    'inline'
                );
            }
        }

        //breadcumb
        $model['entity'] = 'controller.report.saleByProductCategory.entity';
        $model['page'] = 'controller.report.saleByProductCategory.page';
        return $this->render('report/saleByProductCategory.html.twig', $model);
    }

    /**
     * @Route("/report/customer/saleByProduct", name="report_customer_sale_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SaleRepository $saleRepository
     * @param CustomerRepository $customerRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function saleByProductCustomer(Request $request,
                                          Pdf $pdf,
                                          StoreRepository $storeRepository,
                                          SaleRepository $saleRepository,
                                          CustomerRepository $customerRepository,
                                          ProductRepository $productRepository): Response
    {

        $model['byProduct'] = ($request->get('type') !==null)
            ?(int)$request->get('type') :1;

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['customers'] = $customerRepository
            ->findByTypes([
                CustomerTypeConstant::TYPEKEYS['Simple Customer'],
                CustomerTypeConstant::TYPEKEYS['Reseller']
            ]);

        //breadcumb
        $model['entity'] = 'controller.report.saleByProductCustomer.entity';
        $model['page'] = 'controller.report.saleByProductCustomer.page';

        if ($request->isMethod('POST')) {

            $model['customer'] = $customerRepository
                ->find((int) $request->get('customer'));

            if ($model['byProduct']){
                $model['products'] = $productRepository
                    ->getByPeriodDate($model['start'],$model['end'], null,$model['customer']);

                $sales = $saleRepository
                    ->findByPeriodCustomer($model['start'],$model['end'],$model['customer']);

                $model['totalDebt'] = array_sum(array_map(static function(Sale $sale){
                    return $sale->getAmountDebt();
                },$sales));

                $model['totalDiscount'] = array_sum(array_map(static function(Sale $sale){
                    return $sale->getDiscount();
                },$sales));


                $model['totalAmountWithoutDiscount'] = array_sum(array_map(static function($product){
                    return $product['amount'];
                },$model['products']));

                $model['totalAmount'] = $model['totalAmountWithoutDiscount']
                    - $model['totalDiscount'] - $model['totalDebt'];

                $model['totalProfitWithoutDiscount'] = array_sum(array_map(static function($product){
                    return $product['profit'];
                },$model['products']));

                $model['totalProfit'] = $model['totalProfitWithoutDiscount']
                    - $model['totalDiscount'] - $model['totalDebt'];

                $model['totalQty'] = array_sum(array_map(static function($product){
                    return $product['qtySold'];
                },$model['products']));
            }else{
                $model['sales'] = $saleRepository
                    ->findByPeriodCustomer($model['start'],$model['end'], $model['customer']);
                $model['totalDiscount'] = array_sum(array_map(static function(Sale $sale){
                    return $sale->getDiscount();
                },$model['sales']));

                $model['totalDebt'] = array_sum(array_map(static function(Sale $sale){
                    return $sale->getAmountDebt();
                },$model['sales']));

                $model['totalAmount'] = array_sum(array_map(static function(Sale $sale){
                    return $sale->getAmount();
                },$model['sales']))-$model['totalDebt'];

                $model['totalProfit'] = array_sum(array_map(static function(Sale $sale){
                    return $sale->getProfit();
                },$model['sales']))-$model['totalDebt'];
            }


            if ($model['customer'] !== null && $request->get('print')){
                if ($model['byProduct']) {
                    $html = $this->renderView('pdf/report/saleByProductCustomer.html.twig', $model);
                }else{
                    $html = $this->renderView('pdf/report/saleByCustomer.html.twig', $model);
                }

                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                $file = $pdf->getOutputFromHtml($html);
                $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                return new PdfResponse(
                    $file,
                    $filename,
                    'application/pdf',
                    'inline'
                );
            }
        }

        return $this->render('report/saleByProductCustomer.html.twig', $model);
    }

    /**
     * @Route("/report/employee/saleByProduct", name="report_employee_sale_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SaleRepository $saleRepository
     * @param UserRepository $employeeRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function saleByProductEmployee(Request $request,
                                          Pdf $pdf,
                                          StoreRepository $storeRepository,
                                          SaleRepository $saleRepository,
                                          UserRepository $employeeRepository,
                                          ProductRepository $productRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['employees'] = $employeeRepository->findEmployees();

        if ($request->isMethod('POST')) {

            $model['employee'] = $employeeRepository
                ->find((int) $request->get('employee'));

            $model['products'] = $productRepository
                ->getByPeriodDate($model['start'],$model['end'], $model['employee'],null);

            $sales = $saleRepository
                ->findByPeriodUser($model['start'],$model['end'],$model['employee']);

            $model['totalDiscount'] = array_sum(array_map(static function(Sale $sale){
                return $sale->getDiscount();
            },$sales));

            $model['totalDebt'] = array_sum(array_map(static function(Sale $sale){
                return $sale->getAmountDebt();
            },$sales));

            $model['totalAmountWithoutDiscount'] = array_sum(array_map(static function($product){
                return $product['amount'];
            },$model['products']));

            $model['totalAmount'] = $model['totalAmountWithoutDiscount']
                - $model['totalDiscount'] - $model['totalDebt'];

            $model['totalProfitWithoutDiscount'] = array_sum(array_map(static function($product){
                return $product['profit'];
            },$model['products']));

            $model['totalProfit'] = $model['totalProfitWithoutDiscount']
                - $model['totalDiscount'] - $model['totalDebt'];

            $model['totalQty'] = array_sum(array_map(static function($product){
                return $product['qtySold'];
            },$model['products']));

            if ($model['employee'] !== null && $request->get('print')){
                $html = $this->renderView('pdf/report/saleByProductEmployee.html.twig',$model);

                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                $file = $pdf->getOutputFromHtml($html);
                $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                return new PdfResponse(
                    $file,
                    $filename,
                    'application/pdf',
                    'inline'
                );
            }
        }

        //breadcumb
        $model['entity'] = 'controller.report.saleByProductEmployee.entity';
        $model['page'] = 'controller.report.saleByProductEmployee.page';
        return $this->render('report/saleByProductEmployee.html.twig', $model);
    }

    /**
     * @Route("/report/employee", name="report_employee", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param UserRepository $userRepository
     * @return Response
     * @throws Exception
     */
    public function employee(Request $request,
                                  Pdf $pdf,
                                  StoreRepository $storeRepository,
                                  UserRepository $userRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['employees'] = $userRepository
            ->getByPeriodDate($model['start'],$model['end']);

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/employee.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.saleByEmployee.entity';
        $model['page'] = 'controller.report.saleByEmployee.page';
        return $this->render('report/employee.html.twig', $model);
    }

    /**
     * @Route("/report/salary", name="report_salary", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param UserRepository $userRepository
     * @return Response
     * @throws Exception
     */
    public function salary(Request $request,
                          Pdf $pdf,
                          StoreRepository $storeRepository,
                          UserRepository $userRepository): Response
    {


        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model = GlobalConstant::getMonthsAndYear($request);
        $model['store'] = $storeRepository->get();
        $model['salaries'] = $userRepository
            ->findByMonthYearReport($model['monthNow'],$model['year']);

        $model['totalAmount'] = array_sum(array_map(static function($salary){
            return $salary['amount'];
        },$model['salaries']));

        $model['totalAmountRemaining'] = array_sum(array_map(static function($salary){
            return ($salary['salary'] > 0)?$salary['amountRemaining']:$salary[0]->getSalary();
        },$model['salaries']));

        $model['totalSalary'] = array_sum(array_map(static function($salary){
            return ($salary['salary'] > 0)?$salary['salary']:$salary[0]->getSalary();
        },$model['salaries']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/salary.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.salary.entity';
        $model['page'] = 'controller.report.salary.page';
        return $this->render('report/salary.html.twig', $model);
    }


    /**
     * @Route("/report/attendance", name="report_attendance", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param EmployeeService $employeeService
     * @return Response
     * @throws Exception
     */
    public function attendance(Request $request,
                           Pdf $pdf,
                           StoreRepository $storeRepository,
                           EmployeeService $employeeService): Response
    {

        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }


        $model = GlobalConstant::getMonthsAndYear($request);
        $model['store'] = $storeRepository->get();
        $model['attendances'] = $employeeService
            ->getReportAttendance($model['monthNow'],$model['year']);

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/attendance.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.attendance.entity';
        $model['page'] = 'controller.report.attendance.page';
        return $this->render('report/attendance.html.twig', $model);
    }

    /**
     * @Route("/report/supplier/stockByProduct", name="report_supplier_stock_product", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SupplierRepository $supplierRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     */
    public function stockByProductSupplier(Request $request,
                                          Pdf $pdf,
                                          StoreRepository $storeRepository,
                                          SupplierRepository $supplierRepository,
                                          ProductRepository $productRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['suppliers'] = $supplierRepository->findAll();

        if ($request->isMethod('POST')) {
            $model['supplier'] = $supplierRepository
                ->find((int) $request->get('supplier'));

            $model['products'] = $productRepository
                ->getStockByPeriodDate($model['start'],$model['end'],null,$model['supplier'],null);

            if ($model['supplier'] !== null && $request->get('print')){
                $html = $this->renderView('pdf/report/stockByProductSupplier.html.twig',$model);

                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                $file = $pdf->getOutputFromHtml($html);
                $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                return new PdfResponse(
                    $file,
                    $filename,
                    'application/pdf',
                    'inline'
                );
            }
        }

        //breadcumb
        $model['entity'] = 'controller.report.stockByProductSupplier.entity';
        $model['page'] = 'controller.report.stockByProductSupplier.page';
        return $this->render('report/stockByProductSupplier.html.twig', $model);
    }


    /**
     * @Route("/report/file/sale", name="report_file_sale", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param UserRepository $userRepository
     * @param EncashmentService $encashmentService
     * @param CustomerService $customerService
     * @param CustomerRepository $customerRepository
     * @param EncashmentRepository $encashmentRepository
     * @return Response
     * @throws Exception
     */
    public function fileSale(Request $request,
                             Pdf $pdf,
                             StoreRepository $storeRepository,
                             UserRepository $userRepository,
                             EncashmentService $encashmentService,
                             CustomerService $customerService,
                             CustomerRepository $customerRepository,
                             EncashmentRepository $encashmentRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.report.stock.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['employees'] = array_filter($userRepository->findEmployees(), static function(User $user){
            $permissions = array_map(static function(Permission $permission){ return $permission->getCode();},
                $user->getRole()->getPermissions()->toArray());
            return array_search('SALE_NEW',$permissions,true);
        });

        if ($request->isMethod('POST')) {
            $model['employee'] = $userRepository
                ->find((int) $request->get('employee'));


            $encashments = $encashmentRepository
                ->findByPeriod($model['start'],$model['end'],$model['employee']);

            $model['encashments'] = array_map(static function(Encashment $encashment)
            use($encashmentService,$customerService,$customerRepository,$model){
                $customers= $customerService
                    ->getCredits($encashment->getDate(),$encashment->getDate(),
                        $customerRepository->findAll(),$model['employee']);

               $totalCredits = array_sum(array_map(static function($line){
                    return $line['amount'];
                },$customers));

                $file = $encashmentService
                    ->getInventory($encashment->getDate(),$encashment->getDate(),$model['employee']);

                $file = array_filter($file, static function($line){
                    return ($line['qtySold'] > 0);
                });

                $totalAmountSold = array_sum(array_map(static function($line){
                    return $line['amountSold'];
                },$file));

                $totalToDeposit =  $totalAmountSold - $totalCredits + $encashment->getInitialBalance();
                $totalGap = $encashment->getAmountReceived() - $totalToDeposit;

                $encashment->setTotalCredits($totalCredits);
                $encashment->setTotalAmountSold($totalAmountSold);
                $encashment->setTotalToDeposit($totalToDeposit);
                $encashment->setTotalGap($totalGap);

                return $encashment;

            },$encashments);

            $model['totalPositive'] = array_sum(
                array_map(static function(Encashment $encashment){
                    return $encashment->getTotalGap();
                },array_filter($model['encashments'],
                        static function(Encashment $encashment){
                    return $encashment->getTotalGap() > 0;
                })
                )
            );

            $model['totalNegative'] = array_sum(
                array_map(static function(Encashment $encashment){
                    return $encashment->getTotalGap();
                },array_filter($model['encashments'],
                        static function(Encashment $encashment){
                            return $encashment->getTotalGap() < 0;
                        })
                )
            );

            if ($model['employee'] !== null && $request->get('print')){
                $html = $this->renderView('pdf/report/fileSale.html.twig',$model);

                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
                $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
                $file = $pdf->getOutputFromHtml($html);
                $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
                return new PdfResponse(
                    $file,
                    $filename,
                    'application/pdf',
                    'inline'
                );
            }
        }

        //breadcumb
        $model['entity'] = 'controller.report.fileSale.entity';
        $model['page'] = 'controller.report.fileSale.page';
        return $this->render('report/fileSale.html.twig', $model);
    }

    /**
     * @Route("/report/supplier", name="report_supplier", methods={"GET","POST"})
     * @param Request $request
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @param SupplierRepository $supplierRepository
     * @return Response
     * @throws Exception
     */
    public function supplier(Request $request,
                                    Pdf $pdf,
                                    StoreRepository $storeRepository,
                                    SupplierRepository $supplierRepository): Response
    {

        $model['store'] = $storeRepository->get();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['suppliers'] = $supplierRepository
            ->getByPeriodDate($model['start'],$model['end']);

        $model['totalAmount'] = array_sum(array_map(static function($category){
            return $category['amount'];
        },$model['suppliers']));

        $model['qtyPurchase'] = array_sum(array_map(static function($category){
            return $category['qtyPurchase'];
        },$model['suppliers']));

        if ($request->isMethod('POST') && $request->get('print')) {
            $html = $this->renderView('pdf/report/supplier.html.twig',$model);

            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('page-height', $this->setting->getReportHeight()); //105
            $pdf->setOption('page-width', $this->setting->getReportWidth()); //74
            $file = $pdf->getOutputFromHtml($html);
            $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
            return new PdfResponse(
                $file,
                $filename,
                'application/pdf',
                'inline'
            );
        }

        //breadcumb
        $model['entity'] = 'controller.report.supplier.entity';
        $model['page'] = 'controller.report.supplier.page';
        return $this->render('report/supplier.html.twig', $model);
    }
}
