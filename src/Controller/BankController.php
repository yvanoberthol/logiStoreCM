<?php


namespace App\Controller;


use App\Entity\Bank;
use App\Entity\Setting;
use App\Extension\AppExtension;
use App\Form\BankType;
use App\Repository\BankRepository;
use App\Util\ModuleConstant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BankController extends AbstractController
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
     * BankController constructor.
     * @param AppExtension $appExtension
     * @param SessionInterface $session
     */
    public function __construct(AppExtension $appExtension, SessionInterface $session)
    {
        $this->appExtension = $appExtension;
        $this->setting = $session->get('setting');
    }


    /**
     * @Route("/bank", name="bank_index")
     * @param BankRepository $bankRepository
     * @return Response
     */
    public function index(BankRepository $bankRepository)
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['banks'] = $bankRepository->findAll();
        //breadcumb
        $model['entity'] = 'controller.bank.index.entity';
        $model['page'] = 'controller.bank.index.page';
        return $this->render('bank/index.html.twig', $model);
    }

    /**
     * @Route("/bank/updateStatus/{id}", name="bank_update_status")
     * @param Bank $bank
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateStatus(Bank $bank, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $bank->setStatus(!$bank->getStatus());
        $entityManager->persist($bank);
        $entityManager->flush();

        $this->addFlash('success',"controller.bank.edit.flash.success");
        return $this->redirectToRoute('bank_index');
    }

    /**
     * @Route("/bank/new", name="bank_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $bank = new Bank();
        $form = $this->createForm(BankType::class,$bank);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($bank);
            $entityManager->flush();
            $this->addFlash('success',"controller.bank.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.bank.new.entity';
        $model['page'] = 'controller.bank.new.page';
        return $this->render('bank/new.html.twig',$model);
    }


    /**
     * @Route("/bank/edit/{id}", name="bank_edit")
     * @param Bank $bank
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Bank $bank, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $form = $this->createForm(BankType::class,$bank);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($bank);
                $entityManager->flush();

                $this->addFlash('success',"controller.bank.edit.flash.success");

                return $this->redirectToRoute('bank_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.bank.edit.entity';
        $model['page'] = 'controller.bank.edit.page';
        return $this->render('bank/edit.html.twig',$model);
    }

    /**
     * @Route("/bank/delete/{id}", name="bank_delete")
     * @param Bank $bank
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Bank $bank, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $entityManager->remove($bank);
        $entityManager->flush();
        $this->addFlash('success',"controller.bank.delete.flash.success");
        return $this->redirectToRoute('bank_index');
    }


}
