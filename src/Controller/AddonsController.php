<?php


namespace App\Controller;


use App\Entity\Addons;
use App\Entity\Bank;
use App\Repository\AddonsRepository;
use App\Util\ModuleConstant;
use App\Util\RoleConstant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AddonsController extends AbstractController
{

    /**
     * @Route("/addons/new", name="addons_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $addons = new Addons();
        $form = $this->createForm(AddonsType::class,$addons);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($addons);
            $entityManager->flush();
            return $this->redirectToRoute('addons_index');
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.addons.new.entity';
        $model['page'] = 'controller.addons.new.page';
        return $this->render('addons/new.html.twig',$model);
    }

    /**
     * @Route("/addons/updateStatus/{id}", name="addons_update_status")
     * @param Addons $addons
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateStatus(Addons $addons,
                                 EntityManagerInterface $entityManager): RedirectResponse
    {

        if (strtoupper($this->getUser()->getRoles()[0]) !== RoleConstant::ROLE_ADMIN){
            return $this->redirectToRoute('setting_index');
        }

        $addons->setEnabled(!$addons->getEnabled());
        $entityManager->persist($addons);
        $entityManager->flush();

        $this->addFlash('success',"controller.addons.edit.flash.success");
        return $this->redirectToRoute('setting_index');
    }

}
