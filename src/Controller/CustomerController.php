<?php


namespace App\Controller;


use App\Entity\Customer;
use App\Entity\SalePayment;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\SaleRepository;
use App\Repository\UserRepository;
use App\Service\CustomerService;
use App\Util\CustomerTypeConstant;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{

    /**
     * @Route("/customer", name="customer_index")
     * @param CustomerRepository $customerRepository
     * @return Response
     */
    public function index(CustomerRepository $customerRepository)
    {
        $model['customers'] = $customerRepository
            ->findByTypes([
                CustomerTypeConstant::TYPEKEYS['Simple Customer'],
                CustomerTypeConstant::TYPEKEYS['Reseller'],
                ]);
        //breadcumb
        $model['entity'] = 'controller.customer.index.entity';
        $model['page'] = 'controller.customer.index.page';
        return $this->render('customer/index.html.twig', $model);
    }

    /**
     * @Route("/customer/new", name="customer_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class,$customer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($customer);
            $entityManager->flush();
            $this->addFlash('success',"controller.customer.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.customer.new.entity';
        $model['page'] = 'controller.customer.new.page';
        return $this->render('customer/new.html.twig',$model);
    }


    /**
     * @Route("/customer/edit/{id}", name="customer_edit")
     * @param Customer $customer
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Customer $customer, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CustomerType::class,$customer);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($customer);
                $entityManager->flush();

                $this->addFlash('success',"controller.customer.edit.flash.success");

                return $this->redirectToRoute('customer_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.customer.edit.entity';
        $model['page'] = 'controller.customer.edit.page';
        return $this->render('customer/edit.html.twig',$model);
    }

    /**
     * @Route("/customer/delete/{id}", name="customer_delete")
     * @param Customer $customer
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Customer $customer,
                           UserRepository $userRepository,
                           EntityManagerInterface $entityManager): RedirectResponse
    {
        if (strtoupper($customer->getType()) === 'EMPLOYEE'){
            $user  =$userRepository->findOneBy(['customer' => $customer]);
            $user->setCanCustomer(false);
            $customer->setEnabled(false);

            $entityManager->persist($user);
            $entityManager->persist($customer);
        }else{
            $entityManager->remove($customer);
        }

        $entityManager->flush();
        $this->addFlash('success',"controller.customer.delete.flash.success");
        return $this->redirectToRoute('customer_index');
    }

    /**
     * @Route("customer/salePayment/add", name="customer_sale_payment_add", methods={"POST"})
     * @param Request $request
     * @param CustomerService $customerService
     * @param CustomerRepository $customerRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function add(Request $request,
                        CustomerService $customerService,
                        CustomerRepository $customerRepository,
                        PaymentMethodRepository $paymentMethodRepository,
                        EntityManagerInterface $entityManager)
    {

        $amount = (float) $request->get('amount');
        $customer = $customerRepository->find((int) $request->get('customer'));
        if ($customer !== null && ($amount === 0.0 || $amount > $customer->getAmountDebt())){
            $this->addFlash('danger',"controller.salePayment.add.flash.danger1");
            return $this->redirectToRoute('performance_customer',['id' => $customer->getId()]);
        }

        if (!GlobalConstant::compareDate($request->get('date'), new DateTime())){
            $this->addFlash('danger',"controller.salePayment.add.flash.danger2");
            return $this->redirectToRoute('performance_customer',['id' => $customer->getId()]);
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

        return $this->redirectToRoute('performance_customer',['id' => $customer->getId()]);
    }
}
