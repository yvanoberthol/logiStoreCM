<?php

namespace App\Controller;

use App\Dto\PaymentMethodDto;
use App\Entity\Product;
use App\Entity\ProductSale;
use App\Entity\ProductStockSale;
use App\Entity\Sale;

use App\Entity\Setting;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\ProductSaleRepository;
use App\Repository\SaleRepository;
use App\Service\ProductService;
use App\Service\SendMailer;
use App\Util\CustomerTypeConstant;
use App\Util\GlobalConstant;
use App\Util\RandomUtil;
use App\Util\RedirectUtil;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use http\Client;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SaleController extends AbstractController
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
     * @Route("/sale", name="sale_index", methods={"GET","POST"})
     * @param SaleRepository $saleRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(SaleRepository $saleRepository,
                          PaymentMethodRepository $paymentMethodRepository,
                          Request $request): Response
    {
        $intervalDays = $this->setting->getMaxIntervalPeriod();

        if ($this->getUser()->getRole()->getRank() < 2){
            return $this->redirectToRoute('sale_mine');
        }

        $today = (new DateTime())->format('Y-m-d');
        $model['start'] = $request->get('start') ?? new DateTime($today.' 00:00');
        $model['end'] = $request->get('end') ?? new DateTime($today.' 23:59');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.sale.index.flash.danger");
        }

        //dd($model['start']);
        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $format = 'Y-m-d H:i';
            $model['start'] = date($format, strtotime($model['start']));
            $model['end'] = date($format, strtotime($model['end']));
        }

        if ($model['start'] instanceof DateTime && $model['end'] instanceof DateTime){
            $format = 'Y-m-d H:i';
            $model['start'] = ($model['start'])->format($format);
            $model['end'] = ($model['end'])->format($format);
        }

        $model['sales'] = $saleRepository
            ->findSaleByPeriod($model['start'],$model['end']);

        $model['saleByHours'] = [];
        for ($i=0;$i <= 23; $i++){
            $model['saleByHours'][$i] = [array_sum(array_map(
                static function(Sale $sale) use($model,$i){
                    if ($i === (int)$sale->getAddDate()->format('H')){
                        return $sale->getAmountTotalReceived($model['start'],$model['end']);
                    }
                    return 0;
                },$model['sales'])),array_sum(array_map(
                static function(Sale $sale) use($i){
                    if ($i === (int)$sale->getAddDate()->format('H')){
                        return 1;
                    }
                    return 0;
                },$model['sales']))];
        }

        $model['totalAmount'] = array_sum(array_map(
            static function(Sale $sale) use($model){
            return $sale->getAmountTotalReceived($model['start'],$model['end']);
        },$model['sales']));

        $model['totalAmountDebt'] = array_sum(array_map(
            static function(Sale $sale) use($model){
                return ($sale->getAmountDebt() > 0)? ($sale->getAmount() - $sale->getAmountTotalReceived($model['start'],$model['end'])): 0.0 ;
            },$model['sales']));

        $model['totalDiscount'] = array_sum(array_map(
            static function(Sale $sale){
                return $sale->getDiscount();
            },$model['sales']));

        $model['salesDiscount'] = array_filter($model['sales'],
            static function(Sale $sale){
                return $sale->getDiscount() > 0;
            });

        $model['salesDebt'] = array_filter($model['sales'],
            static function(Sale $sale) use($model){
                return ($sale->getAmountDebt() > 0) && ($sale->getAmount()
                    - $sale->getAmountTotalReceived($model['start'],$model['end']) > 0) ;
            });

        $model['totalDiscountDebt'] = array_sum(array_map(
            static function(Sale $sale){
                return $sale->getDiscount();
            },$model['salesDebt']));

        $paymentMethods = $paymentMethodRepository
            ->findAll();

        $saleMethods = [];
        foreach ($paymentMethods as $paymentMethod){
            $saleByMethod =
                $saleRepository->findSaleByMethodPeriod(
                    $model['start'],$model['end'],$paymentMethod
                );

            $paymentMethodDto = new PaymentMethodDto();
            $paymentMethodDto->setName($paymentMethod->getName());
            $paymentMethodDto->setNbSales(count($saleByMethod));
            $paymentMethodDto->setAmountPerceived(array_sum(
                array_map(static function(Sale $sale) use($model){
                return $sale->getAmountTotalReceived($model['start'],$model['end']);
            },$saleByMethod)));

            $paymentMethodDto->setAmountSettled(array_sum(
                array_map(static function(Sale $sale) use($model){
                return
                    $sale->getAmountSettled(
                        new DateTime($model['start']),
                        new DateTime($model['end'])
                    );
            },$saleByMethod)));
            $saleMethods[] = $paymentMethodDto;
        }

        $model['paymentMethods'] = $saleMethods;

        //breadcumb
        $model['entity'] = 'controller.sale.index.entity';
        $model['page'] = 'controller.sale.index.page';
        return $this->render('sale/index.html.twig', $model);
    }


    /**
     * @Route("/sale/unsettled", name="sale_index_unsettled", methods={"GET","POST"})
     * @param SaleRepository $saleRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function saleUnsettled(SaleRepository $saleRepository,
                          Request $request): Response
    {
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
            $this->addFlash('danger',"controller.sale.index.flash.danger");
        }

        //dd($model['start']);
        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $format = 'Y-m-d H:i';
            $model['start'] = date($format, strtotime($model['start']));
            $model['end'] = date($format, strtotime($model['end']));
        }

        if ($model['start'] instanceof DateTime && $model['end'] instanceof DateTime){
            $format = 'Y-m-d H:i';
            $model['start'] = ($model['start'])->format($format);
            $model['end'] = ($model['end'])->format($format);
        }

        $sales = $saleRepository
            ->findSaleByPeriod($model['start'],$model['end']);

        $model['sales'] = array_filter($sales,static function(Sale $sale){
            return $sale->getAmountDebt() > 0;
        });


        //breadcumb
        $model['entity'] = 'controller.sale.unsettled.entity';
        $model['page'] = 'controller.sale.unsettled.page';
        return $this->render('sale/unsettled.html.twig', $model);
    }

    /**
     * @Route("/sale/deleted", name="sale_index_deleted", methods={"GET","POST"})
     * @param SaleRepository $saleRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function deleted(SaleRepository $saleRepository,
                          Request $request): Response
    {

        if ($this->getUser()->getRole()->getRank() < 2){
            return $this->redirectToRoute('sale_mine');
        }

        $today = (new DateTime())->format('Y-m-d');
        $model['start'] = $request->get('start') ?? new DateTime($today.' 00:00');
        $model['end'] = $request->get('end') ?? new DateTime($today.' 23:59');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        //dd($model['start']);
        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $format = 'Y-m-d H:i';
            $model['start'] = date($format, strtotime($model['start']));
            $model['end'] = date($format, strtotime($model['end']));
        }

        if ($model['start'] instanceof DateTime && $model['end'] instanceof DateTime){
            $format = 'Y-m-d H:i';
            $model['start'] = ($model['start'])->format($format);
            $model['end'] = ($model['end'])->format($format);
        }

        $model['sales'] = $saleRepository
            ->findSaleByPeriod($model['start'],$model['end'],null,true);

        //breadcumb
        $model['entity'] = 'controller.sale.deleted.entity';
        $model['page'] = 'controller.sale.deleted.page';
        return $this->render('sale/deleted.html.twig', $model);
    }

    /**
     * @Route("/mySale", name="sale_mine")
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function mySale(SaleRepository $saleRepository): Response
    {
        $model['sales'] = $saleRepository
            ->findByPeriodUser(new DateTime(),new DateTime(),$this->getUser());

        $model['totalAmount'] = array_sum(array_map(
            static function(Sale $sale){
                return $sale->getAmountTotalReceived(new DateTime(),new DateTime());
            },$model['sales']));

        $model['totalAmountDebt'] = array_sum(array_map(
            static function(Sale $sale){
                return $sale->getAmount() - $sale->getAmountTotalReceived(new DateTime(),new DateTime()) ;
            },$model['sales']));

        //breadcumb
        $model['entity'] = 'controller.sale.index.entity';
        $model['page'] = 'controller.sale.index.page';
        return $this->render('sale/mine.html.twig', $model);
    }

    /**
     * @Route("/sale/detail/{id}", name="sale_detail")
     * @param Sale $sale
     * @param StoreRepository $storeRepository
     * @param CustomerRepository $customerRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @return Response
     */
    public function detail(Sale $sale,
                           StoreRepository $storeRepository,
                           CustomerRepository $customerRepository,
                           PaymentMethodRepository $paymentMethodRepository): Response
    {
        $model['sale'] = $sale;
        $model['paymentMethods'] = $paymentMethodRepository->findBy(['status' => true]);
        $model['customers'] = $customerRepository->findBy(['enabled' => true]);
        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        //breadcumb
        $model['entity'] = 'controller.sale.detail.entity';
        $model['page'] = 'controller.sale.detail.page';
        return $this->render('sale/detailSale.html.twig', $model);
    }

    /**
     * @Route("/sale/new", name="sale_new")
     * @param ProductRepository $productRepository
     * @param CustomerRepository $customerRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function new(ProductRepository $productRepository,
                        CustomerRepository $customerRepository,
                        ProductService $productService): Response
    {


        if ($this->setting->getWithUserCategory()){
            $products = [];
            foreach ($this->getUser()->getCategories() as $category){
                $productSelected = array_filter($category->getProducts()->toArray(),
                    static function(Product $product) {
                        return $product->getEnabled();
                    });

                foreach ($productSelected as $product){
                    $products[] = $product;
                }
            }
        }else{
            $products = $productRepository->findBy(['enabled' => true],['addDate' => 'DESC']);
        }

        $model['products'] = $productService->countStocks($products);

        $model['customers'] = $customerRepository
            ->findBy(['type' => CustomerTypeConstant::TYPEKEYS['Simple Customer']]);

        $model['entity'] = 'controller.sale.new.entity';
        $model['page'] = 'controller.sale.new.page';

        if ($this->setting->getWithGuiSale()){
            $page = 'sale/guiadd.html.twig';
        }else if ($this->setting->getProductWithImage()){
            $page = 'sale/addWithImage.html.twig';
        }else{
            $page = 'sale/add.html.twig';
        }

        return $this->render($page, $model);

    }

    /**
     * @Route("/sale/wholesaler/new", name="sale_wholesaler_new")
     * @param ProductRepository $productRepository
     * @param CustomerRepository $customerRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function wholeSalerNew(ProductRepository $productRepository,
                        CustomerRepository $customerRepository,
                        ProductService $productService): Response
    {
        if (!$this->setting->getWithWholeSale()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if ($this->setting->getWithUserCategory()){
            $products = [];
            foreach ($this->getUser()->getCategories() as $category){
                $productSelected = array_filter($category->getProducts()->toArray(),
                    static function(Product $product) {
                        return $product->getEnabled();
                    });

                foreach ($productSelected as $product){
                    $products[] = $product;
                }
            }
        }else{
            $products = $productRepository->findBy(['enabled' => true],['addDate' => 'DESC']);
        }

        $model['products'] = $productService->countStocks($products);
        $model['customers'] = $customerRepository
            ->findBy(['type' => CustomerTypeConstant::TYPEKEYS['Reseller']]);
        $model['entity'] = 'controller.sale.new.entity';
        $model['page'] = 'controller.sale.new.page';

        return $this->render('sale/addForWholeSaler.html.twig', $model);

    }

    /**
     * @Route("/sale/choice", name="sale_choice")
     * @return Response
     */
    public function saleChoice(): Response
    {

        if ($this->setting->getWithWholeSale()
            && $this->isGranted('PERMISSION_VERIFY', 'SALE_WHOLESALER_NEW')) {

            $model['entity'] = 'controller.sale.choice.entity';
            $model['page'] = 'controller.sale.choice.page';

            return $this->render('sale/saleChoice.html.twig',$model);
        }

        return $this->redirectToRoute('sale_new');
    }

    /**
     * @Route("/sale/print/{id}", name="sale_print")
     * @param Sale $sale
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @return Response
     * @throws Exception
     */
    public function print(Sale $sale, Pdf $pdf, StoreRepository $storeRepository): Response
    {

        $model['sale'] = $sale;
        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        $html = $this->renderView('pdf/sale.html.twig',$model);

        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('page-height', $this->setting->getSaleReceiptHeight()); //105
        $pdf->setOption('page-width', $this->setting->getSaleReceiptWidth()); //74
        $pdf->setOption('margin-left', 1);
        $pdf->setOption('margin-right', 1);
        $pdf->setOption('margin-top', 2);
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
     * @Route("/invoice/print/{id}", name="sale_invoice_print")
     * @param Sale $sale
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @return Response
     * @throws Exception
     */
    public function invoicePrint(Sale $sale, Pdf $pdf, StoreRepository $storeRepository): Response
    {

        $model['sale'] = $sale;
        if ($model['sale']->getCustomer() === null
            || empty($model['sale']->getCustomer()->getEmail())){
            return $this->json(false,200);
        }

        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        $html = $this->renderView('pdf/invoice.html.twig',$model);

        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('page-height', $this->setting->getReportHeight());
        $pdf->setOption('page-width',  $this->setting->getReportWidth());
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
     * @Route("/sale/sendByMail", name="sale_sendbymail", methods={"POST","GET"})
     * @param Request $request
     * @param Pdf $pdf
     * @param SendMailer $sendMailer
     * @param SaleRepository $saleRepository
     * @param StoreRepository $storeRepository
     * @return Response
     */
    public function sendByMail(Request $request,
                               Pdf $pdf,
                               SendMailer $sendMailer,
                               SaleRepository $saleRepository,
                               StoreRepository $storeRepository): Response
    {

        $model['sale'] = $saleRepository->find((int) $request->get('id'));

        if ($model['sale']->getCustomer() === null
            || empty($model['sale']->getCustomer()->getEmail())){
            return $this->json(false,200);
        }

        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        $html = $this->renderView('pdf/invoice.html.twig',$model);

        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('page-height', $this->setting->getReportHeight());
        $pdf->setOption('page-width',  $this->setting->getReportWidth());
        $file = $pdf->getOutputFromHtml($html);

        $sended = $sendMailer
            ->sendInvoice($model['sale']->getCustomer(),$file);
        return $this->json($sended,200);
    }


    /**
     * @Route("/sale/delete", name="sale_delete", methods={"POST"})
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param ProductSaleRepository $productSaleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function delete(Request $request, SaleRepository $saleRepository,
                           ProductSaleRepository $productSaleRepository,
                           EntityManagerInterface $entityManager)
    {

        $sale = $saleRepository->find((int) $request->get("id"));

        $this->denyAccessUnlessGranted('SALE_DELETE',$sale);
        $productSales = $productSaleRepository->findBy(['sale' => $sale]);
        foreach ($productSales as $productSale){
            foreach ($productSale->getProductStockSales() as $productSaleStock){
                $entityManager->remove($productSaleStock);
            }
            $entityManager->remove($productSale);
        }

        $entityManager->remove($sale);
        $entityManager->flush();
        $this->addFlash('success',"controller.sale.delete.flash.success");

        if ($this->getUser()->getRole()->getRank() > 1){

            $params = [
                'start' => $request->get('start'),
                'end' => $request->get('end'),
            ];

            return $this->redirectToRoute('sale_index');
        }

        return $this->redirectToRoute('sale_mine');
    }


    /**
     * @Route("/sale/softdelete", name="sale_soft_delete")
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function softdelete(Request $request,
                               SaleRepository $saleRepository,
                               EntityManagerInterface $entityManager): Response
    {

        if (!$this->setting->getWithSoftDelete()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if (strlen(trim($request->get('reason'))) <= 0){
            $this->addFlash('danger',"controller.sale.delete.flash.danger");
            return $this->redirectToRoute('sale_index');
        }

        $sale = $saleRepository->find((int) $request->get('id'));
        $this->denyAccessUnlessGranted('SALE_DELETE',$sale);

        $sale->setDeleted(true);
        $sale->setReason($request->get('reason'));
        $entityManager->persist($sale);
        $entityManager->flush();
        $this->addFlash('success',"controller.sale.delete.flash.success");

        $params = [
            'start' => $request->get('start'),
            'end' => $request->get('end'),
        ];

        return $this->forward('App\Controller\SaleController::deleted', $params);

    }

    /**
     * @Route("/sale/restore", name="sale_restore")
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param ProductService $productService
     * @param ProductSaleRepository $productSaleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function restore(Request $request,
                               SaleRepository $saleRepository,
                               ProductService $productService,
                               ProductSaleRepository $productSaleRepository,
                               EntityManagerInterface $entityManager): RedirectResponse
    {

        $sale = $saleRepository->find((int) $request->get('id'));

        $productSales = $productSaleRepository->findBy(['sale' => $sale]);

        foreach ($productSales as $productSale){
            $productStock = $productService
                ->countQtyRemaining(
                    $productSale->getProductStockSales()[0]->getProductStock()
                );

            if ($productStock !== null &&
                $productStock->getQtyRemaining() < $productSale->getQty()){

                $this->addFlash('danger',"controller.sale.restore.flash.danger");
                return $this->redirectToRoute('sale_index_deleted');
            }
        }

        $sale->setDeleted(false);
        $entityManager->persist($sale);
        $entityManager->flush();

        $this->addFlash('success',"controller.sale.restore.flash.success");
        return $this->redirectToRoute('sale_index_deleted');
    }

    /**
     * @Route("/sale/change/numInvoice", name="sale_change_num_invoice")
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function saleNumInvoice(Request $request,
                                        SaleRepository $saleRepository,
                                        EntityManagerInterface $entityManager): RedirectResponse
    {
        $saleId = (int) $request->get('saleId');
        $sale = $saleRepository->find($saleId);

        if ($sale !== null){

            $numInvoice = (empty($request->get('numInvoice')))?null:$request->get('numInvoice');

            if ($numInvoice !== null){
                $saleByNumInvoice = $saleRepository->findOneBy(['numInvoice'=>$numInvoice]);
                if ($saleByNumInvoice !== null) {
                    $this->addFlash('danger',"controller.sale.changeNumInvoice.flash.danger");
                    return $this->redirectToRoute('sale_detail',['id' => $saleId]);
                }
            }

            $sale->setNumInvoice($numInvoice);

            $entityManager->persist($sale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sale_detail',['id' => $saleId]);
    }

    /**
     * @Route("/sale/change/date", name="sale_change_date")
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function saleChangeDate(Request $request,
                                      SaleRepository $saleRepository,
                                      EntityManagerInterface $entityManager): RedirectResponse
    {

        $date = $request->get('date') ?? new DateTime();
        $date = (!$date instanceof DateTime)? new DateTime($date) : $date;

        $saleId = (int) $request->get('saleId');
        $sale = $saleRepository->find($saleId);
        if ($sale !== null){
            $sale->setAddDate($date);

            $entityManager->persist($sale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sale_detail',['id' => $saleId]);
    }

    /**
     * @Route("/sale/change/customer", name="sale_change_customer")
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param SaleRepository $saleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function saleChangeCustomer(Request $request,
                                         CustomerRepository $customerRepository,
                                         SaleRepository $saleRepository,
                                         EntityManagerInterface $entityManager): RedirectResponse
    {

        $customer = $customerRepository->find((int) $request->get('customer'));

        $saleId = (int) $request->get('saleId');
        $sale = $saleRepository->find($saleId);
        if ($sale !== null && $sale->getCustomer() !== $customer){
            $sale->setCustomer($customer);

            $entityManager->persist($sale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sale_detail',['id' => $saleId]);
    }
}
