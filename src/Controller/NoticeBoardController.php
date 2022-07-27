<?php


namespace App\Controller;


use App\Entity\NoticeBoard;
use App\Entity\NoticeBoardEmployee;
use App\Form\NoticeBoardType;
use App\Repository\BankRepository;
use App\Repository\NoticeBoardEmployeeRepository;
use App\Repository\NoticeBoardRepository;
use App\Repository\UserRepository;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoticeBoardController extends AbstractController
{

    /**
     * @Route("/noticeBoard", name="notice_board_index")
     * @param NoticeBoardRepository $noticeBoardRepository
     * @param UserRepository $userRepository
     * @return Response
     */
    public function index(NoticeBoardRepository $noticeBoardRepository,
                          UserRepository $userRepository)
    {

        $model['noticeBoards'] = $noticeBoardRepository->findAll();
        $model['users'] = $userRepository->findWithRole();

        //breadcumb
        $model['entity'] = 'controller.noticeBoard.index.entity';
        $model['page'] = 'controller.noticeBoard.index.page';
        return $this->render('noticeBoard/index.html.twig', $model);
    }


    /**
     * @Route("/noticeBoard/addUser", name="notice_board_add_user")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param NoticeBoardRepository $noticeBoardRepository
     * @param NoticeBoardEmployeeRepository $noticeBoardEmployeeRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function addUser(Request $request,
                            UserRepository $userRepository,
                            NoticeBoardRepository $noticeBoardRepository,
                            NoticeBoardEmployeeRepository $noticeBoardEmployeeRepository,
                            EntityManagerInterface $entityManager): Response
    {
        $noticeBoard = $noticeBoardRepository
            ->find((int) $request->get('noticeBoard'));

        foreach ($request->get('user') as $userid){
            $user = $userRepository->find((int) $userid);
            if ($noticeBoardEmployeeRepository
                ->findOneBy(['employee' => $user,'noticeBoard' => $noticeBoard]) === null){
                $noticeBoardEmployee = new NoticeBoardEmployee();
                $noticeBoardEmployee->setNoticeBoard($noticeBoard);
                $noticeBoardEmployee->setEmployee($user);
                $entityManager->persist($noticeBoardEmployee);
                $noticeBoard->addNoticeBoardEmployee($noticeBoardEmployee);
            }
        }

        $entityManager->persist($noticeBoard);
        $entityManager->flush();

        $this->addFlash('success',"controller.noticeBoard.addUser.flash.success");

        return $this->redirectToRoute('notice_board_index');
    }

    /**
     * @Route("/noticeBoard/deleteUser/{id}", name="notice_board_delete_user")
     * @param NoticeBoardEmployee $noticeBoardEmployee
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function deleteUser(NoticeBoardEmployee $noticeBoardEmployee,
                            EntityManagerInterface $entityManager): Response
    {

        $entityManager->remove($noticeBoardEmployee);
        $entityManager->flush();

        $this->addFlash('success',"controller.noticeBoard.deleteUser.flash.success");

        return $this->redirectToRoute('notice_board_index');
    }

    /**
     * @Route("/noticeBoard/new", name="notice_board_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {

        $noticeBoard = new NoticeBoard();
        $form = $this->createForm(NoticeBoardType::class,$noticeBoard);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!GlobalConstant::compareDate($noticeBoard->getStart(), $noticeBoard->getEnd())){
                $this->addFlash('danger',"controller.noticeBoard.new.flash.danger");
            }else{
                $noticeBoard->setRecorder($this->getUser());
                $entityManager->persist($noticeBoard);
                $entityManager->flush();
                $this->addFlash('success',"controller.noticeBoard.new.flash.success");
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.noticeBoard.new.entity';
        $model['page'] = 'controller.noticeBoard.new.page';
        return $this->render('noticeBoard/new.html.twig',$model);
    }


    /**
     * @Route("/noticeBoard/edit/{id}", name="notice_board_edit")
     * @param NoticeBoard $noticeBoard
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function edit(NoticeBoard $noticeBoard, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NoticeBoardType::class,$noticeBoard);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                if (!GlobalConstant::compareDate($noticeBoard->getStart(), $noticeBoard->getEnd())){
                    $this->addFlash('danger',"controller.noticeBoard.new.flash.danger");
                }else{
                    $entityManager->persist($noticeBoard);
                    $entityManager->flush();

                    $this->addFlash('success',"controller.noticeBoard.edit.flash.success");

                    return $this->redirectToRoute('notice_board_index');
                }
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.noticeBoard.edit.entity';
        $model['page'] = 'controller.noticeBoard.edit.page';
        return $this->render('noticeBoard/edit.html.twig',$model);
    }

    /**
     * @Route("/noticeBoard/delete/{id}", name="notice_board_delete")
     * @param NoticeBoard $noticeBoard
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(NoticeBoard $noticeBoard, EntityManagerInterface $entityManager): RedirectResponse
    {
        foreach ($noticeBoard->getNoticeBoardEmployees() as $noticeBoardEmployee){
            $entityManager->remove($noticeBoardEmployee);
        }
        $entityManager->remove($noticeBoard);
        $entityManager->flush();
        $this->addFlash('success',"controller.noticeBoard.delete.flash.success");
        return $this->redirectToRoute('notice_board_index');
    }


}
