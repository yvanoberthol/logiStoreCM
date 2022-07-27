<?php


namespace App\Controller;


use App\Entity\Tax;
use App\Form\TaxType;
use App\Repository\TaxRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaxController extends AbstractController
{

    /**
     * @Route("/tax", name="tax_index")
     * @param TaxRepository $taxRepository
     * @return Response
     */
    public function index(TaxRepository $taxRepository): Response
    {
        $model['taxs'] = $taxRepository->findBy([],['name' => 'DESC']);
        //breadcumb
        $model['entity'] = 'controller.tax.index.entity';
        $model['page'] = 'controller.tax.index.page';
        return $this->render('tax/index.html.twig', $model);
    }

    /**
     * @Route("/tax/new", name="tax_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tax = new Tax();
        $form = $this->createForm(TaxType::class,$tax);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($tax);
            $entityManager->flush();
            return $this->redirectToRoute('tax_index');
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.tax.new.entity';
        $model['page'] = 'controller.tax.new.page';
        return $this->render('tax/new.html.twig',$model);
    }


    /**
     * @Route("/tax/edit/{id}", name="tax_edit")
     * @param Tax $tax
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Tax $tax, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaxType::class,$tax);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($tax);
                $entityManager->flush();
                return $this->redirectToRoute('tax_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.tax.edit.entity';
        $model['page'] = 'controller.tax.edit.page';
        return $this->render('tax/edit.html.twig',$model);
    }

    /**
     * @Route("/tax/delete/{id}", name="tax_delete")
     * @param Tax $tax
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Tax $tax, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($tax);
        $entityManager->flush();
        return $this->redirectToRoute('tax_index');
    }

    /**
     * @Route("/tax/updateStatus/{id}",name="tax_update_status")
     * @param Tax $tax
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateStatus(Tax $tax,
                                 EntityManagerInterface $entityManager): RedirectResponse
    {
        $tax->setStatus(!$tax->getStatus());
        $entityManager->persist($tax);
        $entityManager->flush();

        return $this->redirectToRoute('tax_index');

    }

}
