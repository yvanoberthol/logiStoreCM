<?php


namespace App\Controller;


use App\Entity\LossType;
use App\Form\LossTypeType;
use App\Repository\LossTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LossTypeController extends AbstractController
{

    /**
     * @Route("/lossType", name="loss_type_index")
     * @param LossTypeRepository $lossTypeRepository
     * @return Response
     */
    public function index(LossTypeRepository $lossTypeRepository): Response
    {
        $model['lossTypes'] = $lossTypeRepository->findBy([],['name' => 'DESC']);
        //breadcumb
        $model['entity'] = 'controller.lossType.index.entity';
        $model['page'] = 'controller.lossType.index.page';
        return $this->render('lossType/index.html.twig', $model);
    }

    /**
     * @Route("/lossType/new", name="loss_type_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lossType = new LossType();
        $form = $this->createForm(LossTypeType::class,$lossType);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($lossType);
            $entityManager->flush();
            return $this->redirectToRoute('loss_type_index');
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.lossType.new.entity';
        $model['page'] = 'controller.lossType.new.page';
        return $this->render('lossType/new.html.twig',$model);
    }


    /**
     * @Route("/lossType/edit/{id}", name="loss_type_edit")
     * @param LossType $lossType
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(LossType $lossType, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$lossType->getUpdatable())
            return $this->redirectToRoute('loss_type_index');

        $form = $this->createForm(LossTypeType::class,$lossType);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($lossType);
                $entityManager->flush();
                return $this->redirectToRoute('loss_type_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.lossType.edit.entity';
        $model['page'] = 'controller.lossType.edit.page';
        return $this->render('lossType/edit.html.twig',$model);
    }

    /**
     * @Route("/lossType/delete/{id}", name="loss_type_delete")
     * @param LossType $lossType
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(LossType $lossType, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($lossType->getUpdatable()){
            $entityManager->remove($lossType);
            $entityManager->flush();
        }

        return $this->redirectToRoute('loss_type_index');
    }

}
