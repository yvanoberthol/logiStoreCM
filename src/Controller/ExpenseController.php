<?php


namespace App\Controller;


use App\Entity\Expense;
use App\Entity\Setting;
use App\Entity\Transaction;
use App\Extension\AppExtension;
use App\Form\ExpenseType;
use App\Repository\BankRepository;
use App\Repository\ExpenseTypeRepository;
use App\Repository\ExpenseRepository;
use App\Util\GlobalConstant;
use App\Util\ModuleConstant;
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

class ExpenseController extends AbstractController
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
     * @Route("/expense", name="expense_index", methods={"GET","POST"})
     * @param Request $request
     * @param ExpenseTypeRepository $typeRepository
     * @param ExpenseRepository $expenseRepository
     * @return Response
     * @throws Exception
     */
    public function index(Request $request,
                          ExpenseTypeRepository $typeRepository,
                          ExpenseRepository $expenseRepository)
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $intervalDays = (int) $_ENV['MAX_INTERVAL_PERIOD'];

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        $model['typeSearch'] = null;

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
            if ($_POST['type'] !== '0'){
                $model['typeSearch'] = $_POST['type'];
                $model['expenses'] = $expenseRepository
                    ->findExpenseByPeriod($model['start'],$model['end'], (int) $model['typeSearch']);
            }else{
                $model['expenses'] = $expenseRepository
                    ->findExpenseByPeriod($model['start'],$model['end']);
            }
        }else{
            $model['expenses'] = $expenseRepository
                ->findExpenseByPeriod($model['start'],$model['end']);
            $model['typeSearch'] = '0';
        }


        $model['types'] = $typeRepository->findBy(['status' => true]);

        //breadcumb
        $model['entity'] = 'controller.expense.index.entity';
        $model['page'] = 'controller.expense.index.page';
        return $this->render('expense/index.html.twig', $model);
    }

    /**
     * @Route("/expense/new", name="expense_new")
     * @param Request $request
     * @param AppExtension $appExtension
     * @param BankRepository $bankRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request,
                        AppExtension $appExtension,
                        BankRepository $bankRepository,
                        EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $expense = new Expense();
        $form = $this->createForm(ExpenseType::class,$expense);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!GlobalConstant::compareDate($expense->getDate(), new DateTime())){
                $this->addFlash('danger',"controller.expense.new.flash.danger");
            }elseif ($expense->getType() === null){
                $this->addFlash('danger',"controller.expense.new.flash.danger2");
            } elseif ($expense->getPaymentMethod() === null){
                $this->addFlash('danger',"controller.expense.new.flash.danger3");
            }else{
                if ($_ENV['WITH_ACCOUNTING'] === 'true' &&
                    $appExtension->moduleExists(ModuleConstant::MODULES['acc_man']) &&
                    $request->get('transactional') !== null){

                    $bank = $bankRepository->find((int) $request->get('bank'));

                    if ($bank->getBalance() < $expense->getAmount()){
                        $this->addFlash('danger',"controller.expense.new.flash.danger4");
                        return $this->redirectToRoute('expense_new');
                    }
                    $transaction = new Transaction();
                    $transaction->setRecorder($this->getUser());
                    $transaction->setDate($expense->getDate());
                    $transaction->setType('0');
                    $transaction->setBank($bank);
                    $entityManager->persist($transaction);

                    $expense->setTransaction($transaction);
                }

                $entityManager->persist($expense);
                $entityManager->flush();
                $this->addFlash('success',"controller.expense.new.flash.success");
            }
        }

        $model['form'] = $form->createView();
        $model['banks'] = $bankRepository->findAll();
        //breadcumb
        $model['entity'] = 'controller.expense.new.entity';
        $model['page'] = 'controller.expense.new.page';
        return $this->render('expense/new.html.twig',$model);
    }


    /**
     * @Route("/expense/edit/{id}", name="expense_edit")
     * @param Expense $expense
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Expense $expense, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $form = $this->createForm(ExpenseType::class,$expense);

        if ($request->isMethod('POST')){

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $transaction = $expense->getTransaction();
                if ($transaction !== null){
                    $bank = $transaction->getBank();

                    if ($bank->getBalance() < $expense->getAmount()){
                        $this->addFlash('danger',"controller.expense.new.flash.danger4");
                        return $this->redirectToRoute('expense_edit',
                            ['id' => $expense->getId()]);
                    }

                    $transaction->setDate($expense->getDate());
                    $entityManager->persist($transaction);
                }

                $entityManager->persist($expense);

                $entityManager->flush();

                $this->addFlash('success',"controller.expense.edit.flash.success");

                return $this->redirectToRoute('expense_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.expense.edit.entity';
        $model['page'] = 'controller.expense.edit.page';
        return $this->render('expense/edit.html.twig',$model);
    }

    /**
     * @Route("/expense/delete/{id}", name="expense_delete")
     * @param Expense $expense
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Expense $expense, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if ($expense->getTransaction() !== null){
            $entityManager->remove($expense->getTransaction());
        }

        $entityManager->remove($expense);

        $entityManager->flush();
        $this->addFlash('success',"controller.expense.delete.flash.success");
        return $this->redirectToRoute('expense_index');
    }


}
