<?php


namespace App\Controller;


use App\Entity\Setting;
use App\Entity\Transaction;
use App\Extension\AppExtension;
use App\Form\TransactionType;
use App\Repository\BankRepository;
use App\Repository\TransactionRepository;
use App\Util\GlobalConstant;
use App\Util\ModuleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends AbstractController
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
     * ProductionController constructor.
     * @param AppExtension $appExtension
     * @param SessionInterface $session
     */
    public function __construct(AppExtension $appExtension, SessionInterface $session)
    {
        $this->appExtension = $appExtension;
        $this->setting = $session->get('setting');
    }


    /**
     * @Route("/transaction", name="transaction_index", methods={"GET","POST"})
     * @param Request $request
     * @param BankRepository $bankRepository
     * @param TransactionRepository $transactionRepository
     * @return Response
     * @throws Exception
     */
    public function index(Request $request,
                          BankRepository $bankRepository,
                          TransactionRepository $transactionRepository)
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        $model['bankSearch'] = null;

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

        if ($request->isMethod('POST')){
            if ($_POST['bank'] !== '0'){
                $model['bankSearch'] = $_POST['bank'];
                $model['transactions'] = $transactionRepository
                    ->findTransactionByPeriod($model['start'],$model['end'], (int) $model['bankSearch']);
            }else{
                $model['transactions'] = $transactionRepository
                    ->findTransactionByPeriod($model['start'],$model['end'], $model['bankSearch']);
            }
        }else{
            $model['transactions'] = $transactionRepository
                ->findTransactionByPeriod($model['start'],$model['end'], $model['bankSearch']);
            $model['bankSearch'] = '0';
        }


        $model['banks'] = $bankRepository->findAll();

        //breadcumb
        $model['entity'] = 'controller.transaction.index.entity';
        $model['page'] = 'controller.transaction.index.page';
        return $this->render('transaction/index.html.twig', $model);
    }

    /**
     * @Route("/transaction/new", name="transaction_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $transaction = new Transaction();
        $form = $this->createForm(TransactionType::class,$transaction);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!GlobalConstant::compareDate($transaction->getDate(), new DateTime())){
                $this->addFlash('danger',"controller.stockPayment.add.flash.danger2");
            }elseif($transaction->getBank() === null){
                $this->addFlash('danger', "controller.transaction.new.flash.danger");
            }elseif ($transaction->getAmount() <= 0){
                $this->addFlash('danger',"controller.stockPayment.add.flash.danger4");}
            elseif ($transaction->getType() === '0'
                && $transaction->getAmount() > $transaction->getBank()->getBalance()){
                $this->addFlash('danger',"controller.stockPayment.add.flash.danger3");
            }else{
                $entityManager->persist($transaction);
                $entityManager->flush();
                $this->addFlash('success',"controller.transaction.new.flash.success");
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.transaction.new.entity';
        $model['page'] = 'controller.transaction.new.page';
        return $this->render('transaction/new.html.twig',$model);
    }


    /**
     * @Route("/transaction/edit/{id}", name="transaction_edit")
     * @param Transaction $transaction
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Transaction $transaction, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $form = $this->createForm(TransactionType::class,$transaction);

        if ($request->isMethod('POST')){
            $oldAmount = $transaction->getAmount();

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                if ($transaction->getType() === '0'
                    && $transaction->getAmount() > ($transaction->getBank()->getBalance() + $oldAmount)){
                    $this->addFlash('danger',"controller.stockPayment.add.flash.danger3");
                }else{
                    $entityManager->persist($transaction);
                    $entityManager->flush();

                    $this->addFlash('success',"controller.transaction.edit.flash.success");

                    return $this->redirectToRoute('transaction_index');
                }
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.transaction.edit.entity';
        $model['page'] = 'controller.transaction.edit.page';
        return $this->render('transaction/edit.html.twig',$model);
    }

    /**
     * @Route("/transaction/delete/{id}", name="transaction_delete")
     * @param Transaction $transaction
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Transaction $transaction, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithAccounting() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['acc_man'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $entityManager->remove($transaction);
        $entityManager->flush();
        $this->addFlash('success',"controller.transaction.delete.flash.success");
        return $this->redirectToRoute('transaction_index');
    }


}
