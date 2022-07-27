<?php

namespace App\Controller;


use App\Entity\Encashment;
use App\Entity\Permission;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\EncashmentRepository;
use App\Repository\UserRepository;
use App\Service\CustomerService;
use App\Service\EncashmentService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{

    /**
     * @Route("/file/sale/employee",name="file_sale_employee",methods={"GET","POST"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function employee(UserRepository $userRepository): Response
    {

        $model['employees'] = array_filter($userRepository->findEmployees(), static function(User $user){
            $permissions = array_map(static function(Permission $permission){ return $permission->getCode();},
                $user->getRole()->getPermissions()->toArray());
            return array_search('SALE_NEW',$permissions,true);
        });

        //breadcumb
        $model['entity'] = 'controller.file.sale.employee.entity';
        $model['page'] = 'controller.file.sale.employee.page';

        return $this->render('file/sale/employee.html.twig',$model);
    }


    /**
     * @Route("/file/sale/editInitialBalance/{id}",name="file_sale_edit_initial_balance",methods={"GET","POST"})
     * @param User $employee
     * @param Request $request
     * @param EncashmentRepository $encashmentRepository
     * @param CustomerService $customerService
     * @param CustomerRepository $customerRepository
     * @param EncashmentService $encashmentService
     * @return Response
     * @throws Exception
     */
    public function editInitialBalance(User $employee,Request $request,
                                   EncashmentRepository $encashmentRepository,
                                   CustomerService $customerService,
                                   CustomerRepository $customerRepository,
                                   EncashmentService $encashmentService): Response
    {

        $model['employee'] = $employee;

        $model['date'] = $request->get('date') ?? new DateTime();


        if (!$model['date'] instanceof DateTime){
            $model['date'] = new DateTime($model['date']);
        }

        $date = date('Y-m-d');
        if ($model['date'] instanceof DateTime){
            $format = 'Y-m-d';
            $date = ($model['date'])->format($format);
        }

        $encashment = $encashmentRepository->findByDate($date,$employee);

        $file = $encashmentService
            ->getInventory($model['date'],$model['date'],$employee);

        $model['file'] = array_filter($file, static function($line){
            return ($line['qtySold'] > 0);
        });

        $model['totalQtySold'] = array_sum(array_map(static function($line){
            return $line['qtySold'];
        },$model['file']));

        $model['totalAmountSold'] = array_sum(array_map(static function($line){
            return $line['amountSold'];
        },$model['file']));


        $model['customers'] = $customerService
            ->getCredits($model['date'],$model['date'],$customerRepository->findAll(),$employee);

        $model['totalCredits'] = array_sum(array_map(static function($line){
            return $line['amount'];
        },$model['customers']));

        $model['totalEncashment'] = ($encashment === null)?0:$encashment->getAmountReceived();
        $model['totalInitialBalance'] = ($encashment === null)?0:$encashment->getInitialBalance();

        $model['totalToDeposit'] = $model['totalAmountSold'] - $model['totalCredits'] + $model['totalInitialBalance'];

        $model['totalGap'] = $model['totalEncashment'] - $model['totalToDeposit'];

        //breadcumb
        $model['entity'] = 'controller.file.sale.edit.entity';
        $model['page'] = 'controller.file.sale.edit.page';

        return $this->render('file/sale/editInitialBalance.html.twig',$model);
    }


    /**
     * @Route("/file/sale/editEncashment/{id}",name="file_sale_edit_encashment",methods={"GET","POST"})
     * @param User $employee
     * @param Request $request
     * @param EncashmentRepository $encashmentRepository
     * @param CustomerService $customerService
     * @param CustomerRepository $customerRepository
     * @param EncashmentService $encashmentService
     * @return Response
     * @throws Exception
     */
    public function editEncashment(User $employee,Request $request,
                                   EncashmentRepository $encashmentRepository,
                                   CustomerService $customerService,
                                   CustomerRepository $customerRepository,
                                   EncashmentService $encashmentService): Response
    {

        $model['employee'] = $employee;

        $model['date'] = $request->get('date') ?? new DateTime();


        if (!$model['date'] instanceof DateTime){
            $model['date'] = new DateTime($model['date']);
        }

        $date = date('Y-m-d');
        if ($model['date'] instanceof DateTime){
            $format = 'Y-m-d';
            $date = ($model['date'])->format($format);
        }

        $encashment = $encashmentRepository->findByDate($date,$employee);

        $file = $encashmentService
            ->getInventory($model['date'],$model['date'],$employee);

        $model['file'] = array_filter($file, static function($line){
            return ($line['qtySold'] > 0);
        });

        $model['totalQtySold'] = array_sum(array_map(static function($line){
            return $line['qtySold'];
        },$model['file']));

        $model['totalAmountSold'] = array_sum(array_map(static function($line){
            return $line['amountSold'];
        },$model['file']));


        $model['customers'] = $customerService
            ->getCredits($model['date'],$model['date'],$customerRepository->findAll(),$employee);

        $model['totalCredits'] = array_sum(array_map(static function($line){
            return $line['amount'];
        },$model['customers']));

        $model['totalEncashment'] = ($encashment === null)?0:$encashment->getAmountReceived();
        $model['totalInitialBalance'] = ($encashment === null)?0:$encashment->getInitialBalance();

        $model['totalToDeposit'] = $model['totalAmountSold'] - $model['totalCredits'] + $model['totalInitialBalance'];

        $model['totalGap'] = $model['totalEncashment'] - $model['totalToDeposit'];

        //breadcumb
        $model['entity'] = 'controller.file.sale.edit.entity';
        $model['page'] = 'controller.file.sale.edit.page';

        return $this->render('file/sale/editEncashment.html.twig',$model);
    }

    /**
     * @Route("/file/sale/update/{id}",name="file_sale_update",methods={"GET","POST"})
     * @param User $employee
     * @param Request $request
     * @param EncashmentRepository $encashmentRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function update(User $employee, Request $request,
                           EncashmentRepository $encashmentRepository,
                           EntityManagerInterface $entityManager): Response
    {

        $model['employee'] = $employee;

        if ($request->isMethod('POST')){
            if ($request->get('_property') === 'initialBalance'){

                $addDate = new DateTime($request->get('addDate'));

                $date = date('Y-m-d');
                if ($addDate instanceof DateTime){
                    $format = 'Y-m-d';
                    $date = ($addDate)->format($format);
                }


                $encashment = $encashmentRepository->findByDate($date,$employee) ?? new Encashment();

                $encashment->setDate($addDate);
                $encashment->setEmployee($employee);
                $encashment->setInitialBalance((float) $request->get('initialBalance'));
                $encashment->setRecorderName($this->getUser()->getAllName());
                $entityManager->persist($encashment);
                $entityManager->flush();

                return $this->redirectToRoute('file_sale_index',
                    ['id' => $employee->getId(),'date' => $date],307);
            }

            if ($request->get('_property') === 'encashment'){

                $addDate = new DateTime($request->get('addDate'));

                $date = date('Y-m-d');
                if ($addDate instanceof DateTime){
                    $format = 'Y-m-d';
                    $date = ($addDate)->format($format);
                }


                $encashment = $encashmentRepository->findByDate($date,$employee) ?? new Encashment();

                $encashment->setDate($addDate);
                $encashment->setEmployee($employee);
                $encashment->setAmountReceived((float) $request->get('amount'));
                $encashment->setRecorderName($this->getUser()->getAllName());
                $entityManager->persist($encashment);
                $entityManager->flush();

                return $this->redirectToRoute('file_sale_index',
                    ['id' => $employee->getId(),'date' => $date],307);
            }

        }

        return $this->redirectToRoute('file_sale_index',['id' => $employee->getId()]);
    }


    /**
     * @Route("/file/sale/{id}",name="file_sale_index",methods={"GET","POST"})
     * @param User $employee
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param EncashmentRepository $encashmentRepository
     * @param CustomerService $customerService
     * @param EncashmentService $encashmentService
     * @return Response
     * @throws Exception
     */
    public function index(User $employee,Request $request,
                          CustomerRepository $customerRepository,
                          EncashmentRepository $encashmentRepository,
                          CustomerService $customerService,
                          EncashmentService $encashmentService): Response
    {
        if ($this->getUser()->getRole()->getRank() < 2){
            $employee = $this->getUser();
        }
        $model['employee'] = $employee;

        $model['date'] = $request->get('date') ?? new DateTime();


        if (!$model['date'] instanceof DateTime){
            $model['date'] = new DateTime($model['date']);
        }

        $date = date('Y-m-d');
        if ($model['date'] instanceof DateTime){
            $format = 'Y-m-d';
            $date = ($model['date'])->format($format);
        }

        $encashment = $encashmentRepository->findByDate($date,$employee);

        $file = $encashmentService
            ->getInventory($model['date'],$model['date'],$employee);

        $model['file'] = array_filter($file, static function($line){
            return ($line['qtySold'] > 0);
        });

        $model['totalQtySold'] = array_sum(array_map(static function($line){
            return $line['qtySold'];
        },$model['file']));

        $model['totalAmountSold'] = array_sum(array_map(static function($line){
            return $line['amountSold'];
        },$model['file']));


        $model['customers'] = $customerService
            ->getCredits($model['date'],$model['date'],$customerRepository->findAll(),$employee);

        $model['totalCredits'] = array_sum(array_map(static function($line){
            return $line['amount'];
        },$model['customers']));

        $model['totalEncashment'] = ($encashment === null)?0:$encashment->getAmountReceived();
        $model['totalInitialBalance'] = ($encashment === null)?0:$encashment->getInitialBalance();

        $model['totalToDeposit'] = $model['totalAmountSold'] - $model['totalCredits'] + $model['totalInitialBalance'];

        $model['totalGap'] = $model['totalEncashment'] - $model['totalToDeposit'];

        //breadcumb
        $model['entity'] = 'controller.file.sale.index.entity';
        $model['page'] = 'controller.file.sale.index.page';

        return $this->render('file/sale/index.html.twig',$model);
    }

}
