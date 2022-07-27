<?php


namespace App\Controller;


use App\Entity\PaymentMethod;
use App\Form\PaymentMethodType;
use App\Repository\PaymentMethodRepository;
use App\Repository\SaleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentMethodController extends AbstractController
{

    /**
     * @Route("/paymentMethod", name="payment_method_index")
     * @param PaymentMethodRepository $paymentMethodRepository
     * @return Response
     */
    public function index(PaymentMethodRepository $paymentMethodRepository)
    {
        $model['paymentMethods'] = $paymentMethodRepository->findAll();
        //breadcumb
        $model['entity'] = 'controller.paymentMethod.index.entity';
        $model['page'] = 'controller.paymentMethod.index.page';
        return $this->render('paymentMethod/index.html.twig', $model);
    }

    /**
     * @Route("/paymentMethod/updateStatus/{id}", name="payment_method_status")
     * @param PaymentMethod $paymentMethod
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateStatus(PaymentMethod $paymentMethod, EntityManagerInterface $entityManager): RedirectResponse
    {

        $paymentMethod->setStatus(!$paymentMethod->getStatus());
        $entityManager->persist($paymentMethod);
        $entityManager->flush();

        $this->addFlash('success',"controller.paymentMethod.edit.flash.success");
        return $this->redirectToRoute('payment_method_index');
    }

    /**
     * @Route("/paymentMethod/new", name="payment_method_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentMethod = new PaymentMethod();
        $form = $this->createForm(PaymentMethodType::class,$paymentMethod);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($paymentMethod);
            $entityManager->flush();
            $this->addFlash('success',"controller.paymentMethod.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.paymentMethod.new.entity';
        $model['page'] = 'controller.paymentMethod.new.page';
        return $this->render('paymentMethod/new.html.twig',$model);
    }


    /**
     * @Route("/paymentMethod/edit/{id}", name="payment_method_edit")
     * @param PaymentMethod $paymentMethod
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(PaymentMethod $paymentMethod, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaymentMethodType::class,$paymentMethod);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($paymentMethod);
                $entityManager->flush();

                $this->addFlash('success',"controller.paymentMethod.edit.flash.success");

                return $this->redirectToRoute('payment_method_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.paymentMethod.edit.entity';
        $model['page'] = 'controller.paymentMethod.edit.page';
        return $this->render('paymentMethod/edit.html.twig',$model);
    }

    /**
     * @Route("/paymentMethod/delete/{id}", name="payment_method_delete")
     * @param PaymentMethod $paymentMethod
     * @param SaleRepository $saleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(PaymentMethod $paymentMethod,
                           SaleRepository $saleRepository,
                           EntityManagerInterface $entityManager): RedirectResponse
    {

        if ((int)$saleRepository->countByPaymentPeriod($paymentMethod) === 0){
            $entityManager->remove($paymentMethod);
            $entityManager->flush();
            $this->addFlash('success',"controller.paymentMethod.delete.flash.success");
        }


        return $this->redirectToRoute('payment_method_index');
    }

}
