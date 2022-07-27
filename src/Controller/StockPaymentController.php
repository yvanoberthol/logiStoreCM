<?php


namespace App\Controller;


use App\Dto\PaymentMethodDto;
use App\Entity\Supplier;
use App\Entity\Stock;
use App\Entity\StockPayment;
use App\Entity\Setting;
use App\Repository\SupplierRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\StockPaymentRepository;
use App\Repository\StockRepository;
use App\Service\SupplierService;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class StockPaymentController extends AbstractController
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
     * @Route("/stockPayment", name="stock_payment_index", methods={"GET","POST"})
     * @param StockPaymentRepository $stockPaymentRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param SupplierRepository $supplierRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(StockPaymentRepository $stockPaymentRepository,
                          PaymentMethodRepository $paymentMethodRepository,
                          SupplierRepository $supplierRepository,
                          Request $request): Response
    {

        if (!$this->setting->getWithSettlement()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $model['stockPayments'] = null;

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();
        $model['supplier'] = ($request->get('supplier') !== null)
            ?$supplierRepository->find((int) $request->get('supplier')): $request->get('supplier');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.stockPayment.index.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['stockPayments'] = $stockPaymentRepository
            ->groupByPeriodDate($model['start'],$model['end'],null,$model['supplier']);

        $model['suppliers'] =
            $supplierRepository->findAll();

        $model['supplierDebts'] = array_filter($model['suppliers'], static function(Supplier $supplier){
            return $supplier->getAmountDebt() > 0;
        });

        $model['paymentMethods'] = $paymentMethodRepository
            ->findBy(['status' =>true]);

        //breadcumb
        $model['entity'] = 'controller.stockPayment.index.entity';
        $model['page'] = 'controller.stockPayment.index.page';
        return $this->render('stock/payment.html.twig', $model);
    }

    /**
     * @Route("/stockPayment/delete/{id}", name="stock_payment_delete")
     * @param StockPayment $stockPayment
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(StockPayment $stockPayment, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithSettlement()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $stockId = $stockPayment->getStock()->getId();
        $entityManager->remove($stockPayment);
        $entityManager->flush();
        $this->addFlash('success',"controller.stockPayment.delete.flash.success");

        return $this->redirectToRoute('stock_detail',['id' => $stockId]);
    }

    /**
     * @Route("/stockPayment/remove/{id}", name="stock_payment_remove")
     * @param StockPayment $stockPayment
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function remove(StockPayment $stockPayment, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithSettlement()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $entityManager->remove($stockPayment);
        $entityManager->flush();
        $this->addFlash('success',"controller.stockPayment.delete.flash.success");

        return $this->redirectToRoute('stock_payment_index');
    }

    /**
     * @Route("/stockPayment/add", name="stock_payment_add", methods={"POST"})
     * @param Request $request
     * @param StockRepository $stockRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(Request $request, StockRepository $stockRepository,
                        PaymentMethodRepository $paymentMethodRepository,
                        EntityManagerInterface $entityManager)
    {
        if (!$this->setting->getWithSettlement()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

       $stock = $stockRepository
           ->find((int) $request->get('stockId'));

       $amount = (float) $request->get('amount');
       if ($stock !== null && ($amount === 0.0 || $amount > $stock->getAmountDebt())){
           $this->addFlash('danger',"controller.stockPayment.add.flash.danger1");
           return $this->redirectToRoute('stock_detail',['id' => $stock->getId()]);
       }

       if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
           $this->addFlash('danger',"controller.stockPayment.add.flash.danger2");
           return $this->redirectToRoute('stock_detail',['id' => $stock->getId()]);
       }


       $paymentMethod = $paymentMethodRepository
           ->find((int) $request->get('paymentMethod'));

       $stockPayment = new StockPayment();
       $stockPayment
           ->setAddDate(new DateTime($request->get('date')))
           ->setAmount($amount)
           ->setStock($stock)
           ->setPaymentMethod($paymentMethod)
           ->setRecorder($this->getUser());

       $entityManager->persist($stockPayment);
       $entityManager->flush();

        $this->addFlash('success',"controller.stockPayment.add.flash.success");

       return $this->redirectToRoute('stock_detail',['id' => $stock->getId()]);
    }

    /**
     * @Route("stockPayment/new", name="stock_payment_new", methods={"POST"})
     * @param Request $request
     * @param SupplierService $supplierService
     * @param SupplierRepository $supplierRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request,
                        SupplierService $supplierService,
                        SupplierRepository $supplierRepository,
                        PaymentMethodRepository $paymentMethodRepository,
                        EntityManagerInterface $entityManager)
    {

        if (!$this->setting->getWithSettlement()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $amount = (float) $request->get('amount');
        $supplier = $supplierRepository->find((int) $request->get('supplier'));
        if ($supplier !== null && ($amount === 0.0 || $amount > $supplier->getAmountDebt())){
            $this->addFlash('danger',"controller.stockPayment.add.flash.danger1");
            return $this->redirectToRoute('stock_payment_index');
        }

        if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
            $this->addFlash('danger',"controller.stockPayment.add.flash.danger2");
            return $this->redirectToRoute('stock_payment_index');
        }


        $paymentMethod = $paymentMethodRepository
            ->find((int) $request->get('paymentMethod'));

        $stockPayments = $supplierService->getStockPayments($supplier,$amount);

        foreach ($stockPayments as $stockPayment){
            $stockPayment
                ->setAddDate(new DateTime($request->get('date')))
                ->setPaymentMethod($paymentMethod)
                ->setRecorder($this->getUser());

            $entityManager->persist($stockPayment);
        }

        $entityManager->flush();

        $this->addFlash('success',"controller.stockPayment.add.flash.success");

        return $this->redirectToRoute('stock_payment_index');
    }
}
