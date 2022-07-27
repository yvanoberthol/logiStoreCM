<?php


namespace App\Controller;


use App\Dto\PaymentMethodDto;
use App\Entity\Customer;
use App\Entity\Sale;
use App\Entity\SalePayment;
use App\Entity\Setting;
use App\Repository\CustomerRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\SalePaymentRepository;
use App\Repository\SaleRepository;
use App\Service\CustomerService;
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

class SalePaymentController extends AbstractController
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
     * @Route("/salePayment", name="sale_payment_index", methods={"GET","POST"})
     * @param SalePaymentRepository $salePaymentRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param CustomerRepository $customerRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(SalePaymentRepository $salePaymentRepository,
                          PaymentMethodRepository $paymentMethodRepository,
                          CustomerRepository $customerRepository,
                          Request $request): Response
    {
        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $model['salePayments'] = null;

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();
        $model['customer'] = ($request->get('customer') !== null)
            ?$customerRepository->find((int) $request->get('customer')): $request->get('customer');

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.salePayment.index.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['salePayments'] = $salePaymentRepository
            ->groupByPeriodDate($model['start'],$model['end'],null,$model['customer']);

        $model['customers'] =
            $customerRepository->findBy(['enabled' =>true]);

        $model['customerDebts'] = array_filter($model['customers'], static function(Customer $customer){
            return $customer->getAmountDebt() > 0;
        });

        $model['paymentMethods'] = $paymentMethodRepository
            ->findBy(['status' =>true]);

        //breadcumb
        $model['entity'] = 'controller.salePayment.index.entity';
        $model['page'] = 'controller.salePayment.index.page';
        return $this->render('sale/payment.html.twig', $model);
    }

    /**
     * @Route("/salePayment/delete/{id}", name="sale_payment_delete")
     * @param SalePayment $salePayment
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(SalePayment $salePayment, EntityManagerInterface $entityManager): RedirectResponse
    {
        $saleId = $salePayment->getSale()->getId();
        $entityManager->remove($salePayment);
        $entityManager->flush();
        $this->addFlash('success',"controller.salePayment.delete.flash.success");

        return $this->redirectToRoute('sale_detail',['id' => $saleId]);
    }

    /**
     * @Route("/salePayment/remove/{id}", name="sale_payment_remove")
     * @param SalePayment $salePayment
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function remove(SalePayment $salePayment, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($salePayment);
        $entityManager->flush();
        $this->addFlash('success',"controller.salePayment.delete.flash.success");

        return $this->redirectToRoute('sale_payment_index');
    }

    /**
     * @Route("/salePayment/add", name="sale_payment_add", methods={"POST"})
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(Request $request, SaleRepository $saleRepository,
                        PaymentMethodRepository $paymentMethodRepository,
                        EntityManagerInterface $entityManager)
    {
       $sale = $saleRepository
           ->find((int) $request->get('saleId'));

       $amount = (float) $request->get('amount');
       if ($sale !== null && ($amount === 0.0 || $amount > $sale->getAmountDebt())){
           $this->addFlash('danger',"controller.salePayment.add.flash.danger1");
           return $this->redirectToRoute('sale_detail',['id' => $sale->getId()]);
       }

       if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
           $this->addFlash('danger',"controller.salePayment.add.flash.danger2");
           return $this->redirectToRoute('sale_detail',['id' => $sale->getId()]);
       }


       $paymentMethod = $paymentMethodRepository
           ->find((int) $request->get('paymentMethod'));

       $salePayment = new SalePayment();
       $salePayment
           ->setAddDate(new DateTime($request->get('date')))
           ->setAmount($amount)
           ->setSale($sale)
           ->setPaymentMethod($paymentMethod)
           ->setRecorder($this->getUser());

       $entityManager->persist($salePayment);
       $entityManager->flush();

        $this->addFlash('success',"controller.salePayment.add.flash.success");

       return $this->redirectToRoute('sale_detail',['id' => $sale->getId()]);
    }

    /**
     * @Route("salePayment/new", name="sale_payment_new", methods={"POST"})
     * @param Request $request
     * @param CustomerService $customerService
     * @param CustomerRepository $customerRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request,
                        CustomerService $customerService,
                        CustomerRepository $customerRepository,
                        PaymentMethodRepository $paymentMethodRepository,
                        EntityManagerInterface $entityManager)
    {

        $amount = (float) $request->get('amount');
        $customer = $customerRepository->find((int) $request->get('customer'));
        if ($customer !== null && ($amount === 0.0 || $amount > $customer->getAmountDebt())){
            $this->addFlash('danger',"controller.salePayment.add.flash.danger1");
            return $this->redirectToRoute('sale_payment_index');
        }

        if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
            $this->addFlash('danger',"controller.salePayment.add.flash.danger2");
            return $this->redirectToRoute('sale_payment_index');
        }


        $paymentMethod = $paymentMethodRepository
            ->find((int) $request->get('paymentMethod'));

        $salePayments = $customerService->getSalePayments($customer,$amount);

        foreach ($salePayments as $salePayment){
            $salePayment
                ->setAddDate(new DateTime($request->get('date')))
                ->setPaymentMethod($paymentMethod)
                ->setRecorder($this->getUser());

            $entityManager->persist($salePayment);
        }

        $entityManager->flush();

        $this->addFlash('success',"controller.salePayment.add.flash.success");

        return $this->redirectToRoute('sale_payment_index');
    }
}
