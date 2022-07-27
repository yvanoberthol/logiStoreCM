<?php


namespace App\Controller;


use App\Entity\ExpenseType;
use App\Entity\Setting;
use App\Form\ExpenseTypeType;
use App\Repository\ExpenseTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ExpenseTypeController extends AbstractController
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
     * @Route("/expenseType", name="expense_type_index").
     * @param ExpenseTypeRepository $expenseTypeRepository
     * @return Response
     */
    public function index(ExpenseTypeRepository $expenseTypeRepository)
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['expenseTypes'] = $expenseTypeRepository->findAll();
        //breadcumb
        $model['entity'] = 'controller.expenseType.index.entity';
        $model['page'] = 'controller.expenseType.index.page';
        return $this->render('expenseType/index.html.twig', $model);
    }

    /**
     * @Route("/expenseType/updateStatus/{id}", name="expense_type_status")
     * @param ExpenseType $expenseType
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateStatus(ExpenseType $expenseType, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $expenseType->setStatus(!$expenseType->getStatus());
        $entityManager->persist($expenseType);
        $entityManager->flush();

        $this->addFlash('success',"controller.expenseType.edit.flash.success");
        return $this->redirectToRoute('expense_type_index');
    }

    /**
     * @Route("/expenseType/new", name="expense_type_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $expenseType = new ExpenseType();
        $form = $this->createForm(ExpenseTypeType::class,$expenseType);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($expenseType);
            $entityManager->flush();
            $this->addFlash('success',"controller.expenseType.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.expenseType.new.entity';
        $model['page'] = 'controller.expenseType.new.page';
        return $this->render('expenseType/new.html.twig',$model);
    }


    /**
     * @Route("/expenseType/edit/{id}", name="expense_type_edit")
     * @param ExpenseType $expenseType
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(ExpenseType $expenseType, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $form = $this->createForm(ExpenseTypeType::class,$expenseType);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($expenseType);
                $entityManager->flush();

                $this->addFlash('success',"controller.expenseType.edit.flash.success");

                return $this->redirectToRoute('expense_type_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.expenseType.edit.entity';
        $model['page'] = 'controller.expenseType.edit.page';
        return $this->render('expenseType/edit.html.twig',$model);
    }

    /**
     * @Route("/expenseType/delete/{id}", name="expense_type_delete")
     * @param ExpenseType $expenseType
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(ExpenseType $expenseType, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithExpense()){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $entityManager->remove($expenseType);
        $entityManager->flush();
        $this->addFlash('success',"controller.expenseType.delete.flash.success");
        return $this->redirectToRoute('expense_type_index');
    }


}
