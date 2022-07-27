<?php

namespace App\Controller;

use App\Dto\PaymentMethodDto;
use App\Entity\Product;
use App\Entity\ProductAdjust;
use App\Entity\ProductAdjustStock;
use App\Entity\Adjustment;

use App\Entity\Setting;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\ProductAdjustRepository;
use App\Repository\AdjustmentRepository;
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

class AdjustmentController extends AbstractController
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
     * @Route("/adjustment", name="adjustment_index", methods={"GET","POST"})
     * @param AdjustmentRepository $adjustmentRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(AdjustmentRepository $adjustmentRepository,
                          Request $request): Response
    {
        $intervalDays = $this->setting->getMaxIntervalPeriod();


        $today = (new DateTime())->format('Y-m-d');
        $model['start'] = $request->get('start') ?? new DateTime($today);
        $model['end'] = $request->get('end') ?? new DateTime($today);

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.adjustment.index.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $format = 'Y-m-d';
            $model['start'] = date($format, strtotime($model['start']));
            $model['end'] = date($format, strtotime($model['end']));
        }

        if ($model['start'] instanceof DateTime && $model['end'] instanceof DateTime){
            $format = 'Y-m-d';
            $model['start'] = ($model['start'])->format($format);
            $model['end'] = ($model['end'])->format($format);
        }

        $model['adjustments'] = $adjustmentRepository
            ->findAdjustmentByPeriod($model['start'],$model['end']);

        //breadcumb
        $model['entity'] = 'controller.adjustment.index.entity';
        $model['page'] = 'controller.adjustment.index.page';
        return $this->render('adjustment/index.html.twig', $model);
    }

    /**
     * @Route("/adjustment/detail/{id}", name="adjustment_detail")
     * @param Adjustment $adjustment
     * @param StoreRepository $storeRepository
     * @return Response
     */
    public function detail(Adjustment $adjustment,
                           StoreRepository $storeRepository): Response
    {
        $model['adjustment'] = $adjustment;
        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        //breadcumb
        $model['entity'] = 'controller.adjustment.detail.entity';
        $model['page'] = 'controller.adjustment.detail.page';
        return $this->render('adjustment/detailAdjustment.html.twig', $model);
    }

    /**
     * @Route("/adjustment/new", name="adjustment_new")
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

        $model['entity'] = 'controller.adjustment.new.entity';
        $model['page'] = 'controller.adjustment.new.page';

        return $this->render('adjustment/add.html.twig', $model);

    }

    /**
     * @Route("/adjustment/print/{id}", name="adjustment_print")
     * @param Adjustment $adjustment
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @return Response
     * @throws Exception
     */
    public function print(Adjustment $adjustment, Pdf $pdf, StoreRepository $storeRepository): Response
    {

        $model['adjustment'] = $adjustment;
        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        $html = $this->renderView('pdf/adjustment.html.twig',$model);

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
     * @Route("/adjustment/delete", name="adjustment_delete", methods={"POST"})
     * @param Request $request
     * @param AdjustmentRepository $adjustmentRepository
     * @param ProductAdjustRepository $productAdjustRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function delete(Request $request, AdjustmentRepository $adjustmentRepository,
                           ProductAdjustRepository $productAdjustRepository,
                           EntityManagerInterface $entityManager)
    {

        $adjustment = $adjustmentRepository->find((int) $request->get("id"));

        $productAdjusts = $productAdjustRepository->findBy(['adjustment' => $adjustment]);
        foreach ($productAdjusts as $productAdjust){
            foreach ($productAdjust->getProductAdjustStocks() as $productAdjustStock){
                $entityManager->remove($productAdjustStock);
            }
            $entityManager->remove($productAdjust);
        }

        $entityManager->remove($adjustment);
        $entityManager->flush();
        $this->addFlash('success',"controller.adjustment.delete.flash.success");

        return $this->redirectToRoute('adjustment_index');
    }
}
