<?php


namespace App\Controller;


use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoleController extends AbstractController
{

    /**
     * @Route("/role", name="role_index")
     * @param RoleRepository $roleRepository
     * @param PermissionRepository $permissionRepository
     * @return Response
     */
    public function index(RoleRepository $roleRepository, PermissionRepository $permissionRepository): Response
    {
        $model['roles'] = $roleRepository->findAll();
        $model['permissions'] = $permissionRepository->findAll();
        //breadcumb
        $model['entity'] = 'controller.role.index.entity';
        $model['page'] = 'controller.role.index.page';
        return $this->render('role/index.html.twig', $model);
    }

    /**
     * @Route("/role/new", name="role_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class,$role);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $prefix = strtolower(substr($role->getName(),0,4));
            if ($prefix !== 'role'){
                $role->setName('ROLE_'.strtoupper($role->getName()));
            }
            $role->setName(strtoupper($role->getName()));
            $entityManager->persist($role);
            $entityManager->flush();
            $this->addFlash('success',"controller.role.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.role.new.entity';
        $model['page'] = 'controller.role.new.page';
        return $this->render('role/new.html.twig',$model);
    }


    /**
     * @Route("/role/edit/{id}", name="role_edit")
     * @param Role $role
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Role $role,Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$role->getUpdatable())
            return $this->redirectToRoute('role_index');

        $form = $this->createForm(RoleType::class,$role);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($role);
                $entityManager->flush();

                $this->addFlash('success',"controller.role.edit.flash.success");

                return $this->redirectToRoute('role_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.role.edit.entity';
        $model['page'] = 'controller.role.edit.page';
        return $this->render('role/edit.html.twig',$model);
    }

    /**
     * @Route("/role/delete/{id}", name="role_delete")
     * @param Role $role
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Role $role, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($role->getUpdatable()){
            $entityManager->remove($role);
            $entityManager->flush();
            $this->addFlash('success',"controller.role.delete.flash.success");
        }

        return $this->redirectToRoute('role_index');
    }

    /**
     * @Route("/role/addPermission",name="role_add_permission",methods={"POST"})
     * @param Request $request
     * @param PermissionRepository $permissionRepository
     * @param RoleRepository $roleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function addPermissionToRole(Request $request,
                                        PermissionRepository $permissionRepository,
                                        RoleRepository $roleRepository,
                                        EntityManagerInterface $entityManager): RedirectResponse
    {
        $role = $roleRepository->find((int) $request->get('role'));

        foreach ($request->get('permission') as $permissionId) {
            $permission = $permissionRepository->find((int) $permissionId);
            if ($role !== null && $permission !== null
                && !$role->getPermissions()->contains($permission)) {

                $role->addPermission($permission);
                $entityManager->persist($role);

                $entityManager->flush();
            }
        }

        $this->addFlash('success',"controller.role.addPermission.flash.success");

        return $this->redirectToRoute('role_index');
    }

    /**
     * @Route("/role/removePermission",name="role_remove_permission")
     * @param Request $request
     * @param PermissionRepository $permissionRepository
     * @param RoleRepository $roleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function removePermissionToRole(Request $request,
                                        PermissionRepository $permissionRepository,
                                        RoleRepository $roleRepository,
                                        EntityManagerInterface $entityManager): RedirectResponse
    {
        $role = $roleRepository->find((int) $request->get('role'));
        $permission = $permissionRepository->find((int) $request->get('permission'));

        if ( $role!== null && $permission !== null
            && $role->getPermissions()->contains($permission)){

            $role->removePermission($permission);
            $entityManager->persist($role);
            $entityManager->flush();
        }

        $this->addFlash('success',"controller.role.removePermission.flash.success");
        return $this->redirectToRoute('role_index');
    }

}
