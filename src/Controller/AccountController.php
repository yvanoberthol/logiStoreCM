<?php

namespace App\Controller;

use App\Entity\Language;
use App\Entity\PasswordUpdate;

use App\Entity\User;
use App\Form\PasswordUpdateType;
use App\Form\RegisterType;
use App\Repository\LanguageRepository;
use App\Repository\UserRepository;
use App\Util\CountryConstant;
use App\Util\EnvUtil;
use App\Util\GlobalConstant;
use App\Util\SystemUtil;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AccountController extends AbstractController
{

    /**
     * @Route("/login", name="account_login")
     * @param AuthenticationUtils $authenticationUtils
     * @param UserRepository $userRepository
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $userRepository): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')){
            return $this->redirectToRoute('home');
        }

        $model['error'] = $authenticationUtils->getLastAuthenticationError();
        $model['username'] = $authenticationUtils->getLastUsername();

        if ($_ENV['MODE'] === 'demo'){
            $model['users'] = $userRepository->findWithRole();
        }

        return $this->render('account/login.html.twig', $model);
    }

    /**
     * @Route("/logout", name="account_logout")
     * @throws Exception
     */
    public function logout(): void
    {
        throw new \RuntimeException('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @Route("/account/profile", name="account_profile")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function profile(Request $request,
                            EntityManagerInterface $entityManager){

        $userRepository = $entityManager->getRepository(User::class);

        $user = $userRepository
            ->findByNameOrEmail($this->getUser()->getUsername());
        $form = $this->createForm(RegisterType::class, $user);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($user !== null && $form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'controller.account.profile.flash.success');
                return $this->redirectToRoute('account_profile');
            }
        }
        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.account.profile.entity';
        $model['page'] = 'controller.account.profile.page';
        return $this->render('account/profile.html.twig', $model);
    }


    /**
     * @Route("/account/resetpassword", name="account_reset_password")
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function resetPassword(Request $request,
                                  UserPasswordHasherInterface $passwordEncoder,
                            EntityManagerInterface $entityManager){

        $passwordUpdate = new PasswordUpdate();
        $user = $this->getUser();
        $form = $this->createForm(PasswordUpdateType::class, $passwordUpdate);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if (isset($user) && $form->isSubmitted() && $form->isValid()) {
                if (strcmp($passwordUpdate->getOldpassword(), $passwordUpdate->getNewpassword()) !== 0) {
                    if (password_verify($passwordUpdate->getOldpassword(), $user->getPassword())) {
                        $newpassword = $passwordEncoder->hashPassword($user, $passwordUpdate->getNewpassword());
                        $user->setPlainPassword($passwordUpdate->getNewpassword());
                        $user->setPassword($newpassword);
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash('success', 'controller.account.resetPassword.flash.success');
                        return $this->redirectToRoute('account_logout');
                    }

                    $form->get('oldpassword')->addError(new FormError('controller.account.resetPassword.flash.oldpassword'));
                }

                $form->get('newpassword')->addError(new FormError('controller.account.resetPassword.flash.newpassword'));

            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.account.resetpassword.entity';
        $model['page'] = 'controller.account.resetpassword.page';
        return $this->render('account/passwordreset.html.twig', $model);
    }

    /**
     * @Route("/account/changeLanguage", name="account_change_language", methods={"POST","GET"})
     * @param Request $request
     * @param LanguageRepository $languageRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function changeLanguage(Request $request,
                                  LanguageRepository $languageRepository,
                                  EntityManagerInterface $entityManager){
        $model['user'] = $this->getUser();

        $model['languages'] = $languageRepository->findAll();
        $model['countries'] = CountryConstant::COUNTRIES;

        if ($request->isMethod('POST')) {
            $language = $request->get('language');
            $model['user']->setLanguage($language);
            $entityManager->persist($model['user']);
            $entityManager->flush();

            $request->getSession()->set('_locale',$language);
            $request->setDefaultLocale($language);
            return $this->redirectToRoute('account_change_language');
        }

        //breadcumb
        $model['entity'] = 'controller.account.changeLanguage.entity';
        $model['page'] = 'controller.account.changeLanguage.page';
        return $this->render('account/changeLanguage.html.twig', $model);
    }

    /**
     * @Route("/account/deleteLanguage/{id}", name="account_delete_language", methods={"POST","GET"})
     * @param Language $language
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function deleteLanguage(Language $language,
                                   EntityManagerInterface $entityManager){
        $translationDir = $this->getParameter('app.translationdir');
        $filesToDelete = [];

        $files = SystemUtil::getFiles($translationDir);
        foreach ($files as $file){
            $partFiles = explode('.',$file->getFileName());
            if ($partFiles[1] === $language->getCode()){
                $filesToDelete[] = $file->getFileName();
            }
        }
        SystemUtil::removeFiles($filesToDelete,$translationDir);

        $entityManager->remove($language);
        $entityManager->flush();

        $this->addFlash('success', "controller.account.deleteLanguage.success");

        return $this->redirectToRoute('account_change_language');
    }

    /**
     * @Route("/account/importLanguage", name="account_import_language", methods={"POST"})
     * @param Request $request
     * @param KernelInterface $kernel
     * @param LanguageRepository $languageRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function importLanguage(Request $request,
                                   KernelInterface $kernel,
                                   LanguageRepository $languageRepository,
                                   EntityManagerInterface $entityManager){

        $Zipfile = $request->files->get('file');
        $code = $request->get('code');
        $extensionAvailables = ['zip', 'rar'];
        $tempTranslationDir = $this->getParameter('app.temptranslationdir');
        $translationDir = $this->getParameter('app.translationdir');

        if ($languageRepository->findOneBy(['code' => $code]) !== null) {
            $this->addFlash('danger', "controller.account.importLanguage.flash.danger.langExist");
            return $this->redirectToRoute('account_change_language');
        }

        if (!in_array(GlobalConstant::getExtension($Zipfile->getClientOriginalName()), $extensionAvailables, true)) {
            $this->addFlash('danger', "controller.account.importLanguage.flash.danger.typeFileIncorrect");
            return $this->redirectToRoute('account_change_language');
        }

        SystemUtil::cleanDirectory($tempTranslationDir);

        SystemUtil::extractZip($Zipfile,$tempTranslationDir);
        $files = SystemUtil::getFiles($tempTranslationDir);
        if ($this->haveFileNames($files)){
            $this->moveFiles($code,$files,$tempTranslationDir,$translationDir);
        }else{
            $this->addFlash('danger', "controller.account.importLanguage.flash.danger.fileExist");
            return $this->redirectToRoute('account_change_language');
        }

        $language = new Language();
        $language->setCode($request->get('code'));
        $language->setName($request->get('code'));
        $entityManager->persist($language);
        $entityManager->flush();

        EnvUtil::do_command($kernel, 'cache:clear');

        return $this->redirectToRoute('account_change_language');
    }

    private function moveFiles($code,$files,$dir,$destination){
        foreach ($files as $file){
            $partFiles = explode('.',$file->getFileName());
            $newFileName = $partFiles[0].'.'.$code.'.'.$partFiles[1];
            SystemUtil::renameAndMoveFile($file->getFileName(),$newFileName,$dir,$destination);
        }
    }

    private function haveFileNames($files){
        $searchFiles = ['language','messages','month',
            'permission','security','validators','VichUploaderBundle'];

        $files = array_map(static function($file){
            return explode('.',$file->getFileName())[0];
        },$files);

        foreach ($searchFiles as $searchFile){
           if(!in_array($searchFile,$files,true)){
                return false;
           }
        }

        return true;
    }
}
