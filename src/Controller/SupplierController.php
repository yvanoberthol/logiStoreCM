<?php


namespace App\Controller;


use App\Entity\Setting;
use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\PaymentMethodRepository;
use App\Repository\SupplierRepository;
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

class SupplierController extends AbstractController
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
     * @Route("/supplier", name="supplier_index")
     * @param SupplierRepository $supplierRepository
     * @return Response
     */
    public function index(SupplierRepository $supplierRepository)
    {
        $model['suppliers'] = $supplierRepository->findAll();
        //breadcumb
        $model['entity'] = 'controller.supplier.index.entity';
        $model['page'] = 'controller.supplier.index.page';
        return $this->render('supplier/index.html.twig', $model);
    }

    /**
     * @Route("/supplier/new", name="supplier_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $supplier = new Supplier();
        $form = $this->createForm(SupplierType::class,$supplier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($supplier);
            $entityManager->flush();
            $this->addFlash('success',"controller.supplier.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.supplier.new.entity';
        $model['page'] = 'controller.supplier.new.page';
        return $this->render('supplier/new.html.twig',$model);
    }


    /**
     * @Route("/supplier/edit/{id}", name="supplier_edit")
     * @param Supplier $supplier
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Supplier $supplier, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SupplierType::class,$supplier);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($supplier);
                $entityManager->flush();

                $this->addFlash('success',"controller.supplier.edit.flash.success");

                return $this->redirectToRoute('supplier_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.supplier.edit.entity';
        $model['page'] = 'controller.supplier.edit.page';
        return $this->render('supplier/edit.html.twig',$model);
    }

    /**
     * @Route("/supplier/delete/{id}", name="supplier_delete")
     * @param Supplier $supplier
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Supplier $supplier, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($supplier);
        $entityManager->flush();
        $this->addFlash('success',"controller.supplier.delete.flash.success");
        return $this->redirectToRoute('supplier_index');
    }

    /**
     * @Route("supplier/stockPayment/add", name="supplier_stock_payment_add", methods={"POST"})
     * @param Request $request
     * @param SupplierService $supplierService
     * @param SupplierRepository $supplierRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(Request $request,
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
            return $this->redirectToRoute('performance_supplier',['id' => $supplier->getId()]);
        }

        if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
            $this->addFlash('danger',"controller.stockPayment.add.flash.danger2");
            return $this->redirectToRoute('performance_supplier',['id' => $supplier->getId()]);
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

        return $this->redirectToRoute('performance_supplier',['id' => $supplier->getId()]);
    }

}
