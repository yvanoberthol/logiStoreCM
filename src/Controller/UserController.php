<?php

namespace App\Controller;

use App\Entity\Attendance;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\AttendanceRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Util\AttendanceStatusConstant;
use App\Util\GlobalConstant;
use App\Util\RandomUtil;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractController
{

    /**
     * @Route("/user", name="user_index")
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @return Response
     */
    public function index(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        //bredcumb
        $model['entity'] = 'controller.user.index.entity';
        $model['page'] = 'controller.user.index.page';
        $model['users'] = $userRepository->findWithRole();
        $model['roles'] = $roleRepository->findWithoutAdmin();
        return $this->render('user/index.html.twig', $model);
    }


    /**
     * @Route("/user/new", name="user_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ParameterBagInterface $parameterBag
     * @param UserPasswordHasherInterface $passwordEncoder
     * @return Response
     */
    public function new(Request $request,
                        EntityManagerInterface $entityManager,
                        ParameterBagInterface $parameterBag,
                        UserPasswordHasherInterface $passwordEncoder): Response
    {
        $mode = $parameterBag->get('app.mode');
        if($mode === 'demo'){
            $this->addFlash('danger','home.demo.denied');
            return $this->redirectToRoute('home');
        }


        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $randomString = '123456';
            $user->setPlainPassword($randomString);
            $user->setPassword($passwordEncoder->hashPassword($user, $randomString));
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', "controller.user.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.user.new.entity';
        $model['page'] = 'controller.user.new.page';
        return $this->render('user/new.html.twig', $model);
    }


    /**
     * @Route("/user/setpassword", name="user_set_password")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param UserPasswordHasherInterface $passwordEncoder
     * @return Response
     */
    public function setpassword(Request $request,
                        EntityManagerInterface $entityManager,
                        UserRepository $userRepository,
                        UserPasswordHasherInterface $passwordEncoder): Response
    {



        $users = $userRepository->findAll();

        foreach ($users as $user){
            $plainPassword = $user->getPlainPassword();
            $user->setPassword($passwordEncoder->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
        }
        $entityManager->flush();

        return $this->redirectToRoute('account_login');

    }

    /**
     * @Route("/user/updateStatus/{id}",name="user_update_status")
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateStatus(User $user,
                                 EntityManagerInterface $entityManager): RedirectResponse
    {
        $user->setEnabled(!$user->getEnabled());
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('user_index');

    }

    /**
     * @Route("/user/updateCustomer/{id}",name="user_update_customer")
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function updateCanCustomer(User $user,
                                 EntityManagerInterface $entityManager): RedirectResponse
    {
        $user->setCanCustomer(!$user->getCanCustomer());
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('user_index');

    }

    /**
     * @Route("/user/changeRole",name="user_change_role",methods={"POST"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function changeRole(Request $request,
                                 UserRepository $userRepository,
                                 RoleRepository $roleRepository,
                                 EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $userRepository->find((int) $request->get('user'));
        $role = $roleRepository->find((int) $request->get('role'));

        if ($user !== null && $role !== null && $user->getRole() !== $role){
            $user->setRole($role);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', "controller.user.changeRole.flash.success");
        }

        return $this->redirectToRoute('user_index');

    }

    /**
     * @Route("/user/detail/{id}", name="user_detail")
     * @param User $user
     * @param ProductCategoryRepository $productCategoryRepository
     * @return Response
     */
    public function detail(User $user,
                           ProductCategoryRepository $productCategoryRepository): Response
    {


        $model['user'] = $user;

        $model['categories'] = $productCategoryRepository->findAll();


        //breadcumb
        $model['entity'] = 'controller.user.detail.entity';
        $model['page'] = 'controller.user.detail.page';

        return $this->render('user/detailUser.html.twig',$model);
    }

    /**
     * @Route("/user/addCategory", name="user_add_category")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ProductCategoryRepository $productCategoryRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addCategory(Request $request,
                                  UserRepository $userRepository,
                                  ProductCategoryRepository $productCategoryRepository,
                                  EntityManagerInterface $entityManager): Response
    {

        $user = $userRepository->find($request->get('user'));

        foreach ($request->get('category') as $categoryId) {
            $category = $productCategoryRepository->find((int) $categoryId);
            if ($user !== null && $category !== null
                && !$user->getCategories()->contains($category)) {

                $user->addCategory($category);
                $entityManager->persist($user);

                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('user_detail',['id' => $user->getId()]);
    }

    /**
     * @Route("/user/removeCategory", name="user_remove_category")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param ProductCategoryRepository $productCategoryRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function removeCategory(Request $request,
                                   UserRepository $userRepository,
                                   ProductCategoryRepository $productCategoryRepository,
                                   EntityManagerInterface $entityManager): Response
    {

        $user = $userRepository->find($request->get('user'));
        $category = $productCategoryRepository->find($request->get('category'));
        if ($user !== null && $category !== null){
            $user->removeCategory($category);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_detail',['id' => $user->getId()]);
    }

}
