<?php


namespace App\Controller;


use App\Entity\Permission;
use App\Repository\PermissionRepository;
use App\Util\ShortcutConstant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShortCutController extends AbstractController
{

    /**
     * @Route("/shortcut", name="shortcut_index")
     * @param PermissionRepository $permissionRepository
     * @return Response
     */
    public function index(PermissionRepository $permissionRepository)
    {
        $model['permissions'] = $permissionRepository->findBy([],['code' => 'DESC']);
        $model['shortcuts'] = array_filter($model['permissions'],
                static function(Permission $permission){
                    return ($permission->getShortcut() !== null && $permission->getShortcut() !== '');
                });

        $model['keys'] = ShortcutConstant::VALUES;

        //breadcumb
        $model['entity'] = 'controller.shortcut.index.entity';
        $model['page'] = 'controller.shortcut.index.page';
        return $this->render('shortcut/index.html.twig', $model);
    }

    /**
     * @Route("/shortcut/new", name="shortcut_new", methods={"POST"})
     * @param Request $request
     * @param PermissionRepository $permissionRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request,
                        PermissionRepository $permissionRepository,
                        EntityManagerInterface $entityManager): Response
    {

        $permissionId = (int) $request->get('permission');
        $shortcut = $request->get('shortcut');

        $permission = $permissionRepository->findOneBy(['id'=>$permissionId,
            'shortcut' =>$shortcut]);

        if ($permission === null){

            $permission = $permissionRepository->find($permissionId);
            $permission->setShortcut($shortcut);

            $entityManager->persist($permission);
            $entityManager->flush();
        }

        return $this->redirectToRoute('shortcut_index');
    }

    /**
     * @Route("/shortcut/delete/{id}", name="shortcut_delete")
     * @param Permission $permission
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Permission $permission, EntityManagerInterface $entityManager): RedirectResponse
    {
        $permission->setShortcut(null);
        $entityManager->persist($permission);
        $entityManager->flush();
        return $this->redirectToRoute('shortcut_index');
    }

}
