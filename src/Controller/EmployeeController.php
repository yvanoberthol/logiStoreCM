<?php


namespace App\Controller;


use App\Entity\Attendance;
use App\Entity\EmployeeFee;
use App\Entity\SalaryPayment;
use App\Entity\Sale;
use App\Entity\Setting;
use App\Entity\User;
use App\Extension\AppExtension;
use App\Form\EmployeeType;
use App\Repository\AttendanceRepository;
use App\Repository\CustomerRepository;
use App\Repository\EmployeeFeeRepository;
use App\Repository\ProductRepository;
use App\Repository\SalaryPaymentRepository;
use App\Repository\SaleRepository;
use App\Repository\UserRepository;
use App\Util\AttendanceStatusConstant;
use App\Util\GlobalConstant;
use App\Util\ModuleConstant;
use App\Util\RoleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EmployeeController extends AbstractController
{
    /**
     * @var AppExtension
     */
    private $appExtension;
    /**
     * @var Setting
     */
    private $setting;

    /**
     * EmployeeController constructor.
     * @param AppExtension $appExtension
     * @param RequestStack $requestStack
     */
    public function __construct(AppExtension $appExtension,
                                RequestStack $requestStack)
    {
        $this->appExtension = $appExtension;
        $this->setting = $requestStack->getSession()->get('setting');
    }


    /**
     * @Route("/employee", name="employee_index")
     * @param UserRepository $employeeRepository
     * @return Response
     */
    public function index(UserRepository $employeeRepository)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['employees'] = $employeeRepository->findEmployees();
        //breadcumb
        $model['entity'] = 'controller.employee.index.entity';
        $model['page'] = 'controller.employee.index.page';
        return $this->render('employee/index.html.twig', $model);
    }

    /**
     * @Route("/employee/attendance", name="employee_attendance")
     * @param UserRepository $employeeRepository
     * @return Response
     */
    public function employeeAttendance(UserRepository $employeeRepository)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $model['employees'] = $employeeRepository->findEmployees();
        //breadcumb
        $model['entity'] = 'controller.employee.attendance.index.entity';
        $model['page'] = 'controller.employee.attendance.index.page';
        return $this->render('hrm/attendance.html.twig', $model);
    }

    /**
     * @Route("/employee/salary", name="employee_salary")
     * @param UserRepository $employeeRepository
     * @return Response
     */
    public function employeePayment(UserRepository $employeeRepository)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $model['employees'] = $employeeRepository->findEmployees();
        //breadcumb
        $model['entity'] = 'controller.employee.salary.entity';
        $model['page'] = 'controller.employee.salary.page';
        return $this->render('hrm/salary.html.twig', $model);
    }

    /**
     * @Route("/employee/fee", name="employee_fee")
     * @param UserRepository $userRepository
     * @param EmployeeFeeRepository $employeeFeeRepository
     * @param SaleRepository $saleRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function employeeFees(UserRepository $userRepository,
                                 EmployeeFeeRepository $employeeFeeRepository,
                                 SaleRepository $saleRepository, Request $request)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $employees = $userRepository->findEmployees();

        $model['employees'] = [];
        foreach ($employees as $employee){
            $line['customer'] = $employee;
            $line['totalDebt'] = 0;

            $customer = $employee->getCustomer();
            if ($customer !== null){

                $sales = $saleRepository
                    ->findByPeriodCustomer($model['start'],$model['end'],$customer);


                $totalDebt= array_sum(array_map(static function(Sale $sale){
                    return $sale->getAmountDebt();
                },$sales));

                $line['totalDebt'] += $totalDebt;

            }

            $fees = $employeeFeeRepository
                ->groupByPeriodDate($model['start'],$model['end'],$employee);

            $totalFees = array_sum(array_map(static function(EmployeeFee $employeeFee){
                return $employeeFee->getAmount();
            },$fees));
            $line['totalDebt'] += $totalFees;

            $model['employees'][] = $line;
        }

        //breadcumb
        $model['entity'] = 'controller.employee.fee.entity';
        $model['page'] = 'controller.employee.fee.page';
        return $this->render('hrm/fee.html.twig', $model);
    }

    /**
     * @Route("/employee/updateCustomer/{id}",name="employee_update_customer")
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

        return $this->redirectToRoute('employee_index');

    }

    /**
     * @Route("/employee/new", name="employee_new")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }
        $employee = new User();
        $form = $this->createForm(EmployeeType::class,$employee);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $entityManager->persist($employee);
            $entityManager->flush();
            $this->addFlash('success',"controller.employee.new.flash.success");
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.employee.new.entity';
        $model['page'] = 'controller.employee.new.page';
        return $this->render('employee/new.html.twig',$model);
    }


    /**
     * @Route("/employee/edit/{id}", name="employee_edit")
     * @param User $employee
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(User $employee, Request $request, EntityManagerInterface $entityManager): Response
    {

        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if ($employee->getRole() !== null &&
            $employee->getRole()->getTitle() === RoleConstant::ADMIN){
            $this->addFlash('danger',"controller.employee.edit.flash.danger");
            return $this->redirectToRoute('employee_index');
        }

        $form = $this->createForm(EmployeeType::class,$employee);

        if ($request->isMethod('POST')){
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($employee);
                $entityManager->flush();

                $this->addFlash('success',"controller.employee.edit.flash.success");

                return $this->redirectToRoute('employee_index');
            }
        }

        $model['form'] = $form->createView();
        //breadcumb
        $model['entity'] = 'controller.employee.edit.entity';
        $model['page'] = 'controller.employee.edit.page';
        return $this->render('employee/edit.html.twig',$model);
    }

    /**
     * @Route("/employee/delete/{id}", name="employee_delete")
     * @param User $employee
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(User $employee, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if ($employee->getRole() !== null &&
            $employee->getRole()->getTitle() !== RoleConstant::ADMIN){

            $entityManager->remove($employee);
            $entityManager->flush();
            $this->addFlash('success',"controller.employee.delete.flash.success");
        }

        return $this->redirectToRoute('employee_index');
    }

    /**
     * @Route("/employee/attendance/delete/{id}",name="employee_attendance_delete")
     * @param Attendance $attendance
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function deleteAttendance(Attendance $attendance,
                                     EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $employeeId = $attendance->getUser()->getId();
        $entityManager->remove($attendance);
        $entityManager->flush();

        return $this->redirectToRoute('employee_attendance_index',['id' => $employeeId]);

    }

    /**
     * @Route("/employee/attendance/add",name="employee_attendance_add")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param AttendanceRepository $attendanceRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function addAttendance(Request $request,
                                  UserRepository $userRepository,
                                  AttendanceRepository $attendanceRepository,
                                  EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $employee = $userRepository->find((int) $request->get('employeeId'));

        $attendance = $attendanceRepository
            ->findOneBy([
                'date' => new DateTime($request->get('date')),
                'user' => $employee]);

        if ($attendance === null){
            $attendance = new Attendance();
            $attendance->setDate(new DateTime($request->get('date')));
            $attendance->setStatus($request->get('statut'));
            $attendance->setUser($employee);
        }else{
            $attendance->setStatus($request->get('statut'));
        }

        $entityManager->persist($attendance);
        $entityManager->flush();


        return $this->redirectToRoute('employee_attendance_index',['id' => $employee->getId()]);

    }

    /**
     * @Route("/employee/{id}/attendance",name="employee_attendance_index")
     * @param User $employee
     * @param Request $request
     * @param AttendanceRepository $attendanceRepository
     * @return Response
     * @throws Exception
     */
    public function attendance(User $employee,
                               Request $request,
                               AttendanceRepository $attendanceRepository)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model = GlobalConstant::getMonthsAndYear($request);
        $attendances = $attendanceRepository
            ->findByMonthYear($model['monthNow'],$model['year'],$employee);

        $model['statusConstant'] = AttendanceStatusConstant::STATUS;
        $model['statusSelected'] = $request->get('statut') ?? 'A';
        $model['statusAlert'] =
            AttendanceStatusConstant::STATUSALERT[$model['statusSelected']];

        $model['attendances'] = array_filter($attendances,
            static function(Attendance $attendance) use($model){
                return strtoupper($attendance->getStatus()) === $model['statusSelected'];
            });

        $model['absentCount'] = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'A';
            }));

        $model['lateCount'] = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'LA';
            }));

        $model['holidayCount'] = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'H';
            }));

        $model['leaveCount'] = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'LE';
            }));

        $nbDaysOfMonth = cal_days_in_month(1,(int)$model['monthNow'],(int)$model['year']);
        $model['presentCount'] = $nbDaysOfMonth - count($attendances);

        $model['employee'] = $employee;

        //breadcumb
        $model['entity'] = 'controller.employee.attendance.index.entity';
        $model['page'] = 'controller.employee.attendance.index.page';

        return $this->render('employee/attendance.html.twig', $model);

    }

    /**
     * @Route("/employee/{id}/salary",name="employee_salary_index")
     * @param User $employee
     * @param Request $request
     * @param SalaryPaymentRepository $salaryPaymentRepository
     * @return Response
     */
    public function salary(User $employee,
                               Request $request,
                               SalaryPaymentRepository $salaryPaymentRepository)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model = GlobalConstant::getMonthsAndYear($request);
        $model['payments'] = $salaryPaymentRepository
            ->findByMonthYear($model['monthNow'],$model['year'],$employee);


        $model['employee'] = $employee;

        //breadcumb
        $model['entity'] = 'controller.employee.salary.index.entity';
        $model['page'] = 'controller.employee.salary.index.page';

        return $this->render('employee/salary.html.twig', $model);

    }

    /**
     * @Route("/employee/{id}/fee",name="employee_fee_index",methods={"GET","POST"})
     * @param User $employee
     * @param Request $request
     * @param SaleRepository $saleRepository
     * @param ProductRepository $productRepository
     * @param EmployeeFeeRepository $employeeFeeRepository
     * @return Response
     * @throws Exception
     */
    public function fee(User $employee,
                        Request $request,
                        SaleRepository $saleRepository,
                        ProductRepository $productRepository,
                        EmployeeFeeRepository $employeeFeeRepository)
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['employee'] = $employee;
        $model['totalDebt'] = 0;

        $customer = $employee->getCustomer();
        if ($customer !== null){

            $model['sales'] = $saleRepository
                ->findByPeriodCustomer($model['start'],$model['end'],$customer);

            $model['products'] = $productRepository
                ->saleByCustomer($customer,$model['start'],$model['end']);


            $totalDebt= array_sum(array_map(static function(Sale $sale){
                return $sale->getAmountDebt();
            },$model['sales']));

            $model['totalDebt'] += $totalDebt;

        }

        $model['fees'] = $employeeFeeRepository
            ->groupByPeriodDate($model['start'],$model['end'],$employee);

        $totalFees = array_sum(array_map(static function(EmployeeFee $employeeFee){
            return $employeeFee->getAmount();
        },$model['fees']));
        $model['totalDebt'] += $totalFees;

        //breadcumb
        $model['entity'] = 'controller.employee.fee.index.entity';
        $model['page'] = 'controller.employee.fee.index.page';

        return $this->render('employee/fee.html.twig', $model);

    }

    /**
     * @Route("/employee/fee/add",name="employee_fee_add")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function addFee(Request $request,
                              UserRepository $userRepository,
                              EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if (((int) $request->get('amount')) <= 0){
            $this->addFlash('danger',"controller.employee.salary.flash.danger2");
        }


        $employee = $userRepository->find((int) $request->get('employeeId'));

        $fee = new EmployeeFee();
        $fee->setAddDate(new DateTime($request->get('date')));
        $fee->setAmount((float)$request->get('amount'));
        $fee->setReason($request->get('reason'));
        $fee->setEmployee($employee);
        $entityManager->persist($fee);
        $entityManager->flush();


        return $this->redirectToRoute('employee_fee_index',['id' => $employee->getId()]);
    }

    /**
     * @Route("/employee/fee/delete/{id}",name="employee_fee_delete")
     * @param EmployeeFee $employeeFee
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function deleteFee(EmployeeFee $employeeFee,
                                 EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $employeeId = $employeeFee->getEmployee()->getId();
        $entityManager->remove($employeeFee);
        $entityManager->flush();

        return $this->redirectToRoute('employee_fee_index',['id' => $employeeId]);

    }

    /**
     * @Route("/employee/salary/add",name="employee_salary_add")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param SalaryPaymentRepository $salaryPaymentRepository
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     * @throws Exception
     */
    public function addSalary(Request $request,
                                  UserRepository $userRepository,
                                  SalaryPaymentRepository $salaryPaymentRepository,
                                  EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        if (((int) $request->get('amount')) <= 0){
            $this->addFlash('danger',"controller.employee.salary.flash.danger2");
        }


        $employee = $userRepository->find((int) $request->get('employeeId'));
        $salaryPayments = $salaryPaymentRepository
            ->findByMonthYear((int) $request->get('month'),
                (int) $request->get('year'), $employee);

        $salaryPaid = array_sum(array_map(static function(SalaryPayment $salaryPayment){
            return $salaryPayment->getAmount();
        },$salaryPayments));

        $salaryLied = (empty($salaryPayments))?$employee->getSalary():$salaryPayments[0]->getSalary();

        if (($salaryPaid + (int) $request->get('amount')) <= $salaryLied){
            $salaryPayment = new SalaryPayment();
            $salaryPayment->setMonth((int) $request->get('month'));
            $salaryPayment->setYear((int) $request->get('year'));
            $salaryPayment->setAmount((float)$request->get('amount'));
            $salaryPayment->setSalary($salaryLied);
            $salaryPayment->setEmployee($employee);
            $entityManager->persist($salaryPayment);
            $entityManager->flush();
        }else{
            $this->addFlash('danger',"controller.employee.salary.flash.danger");
        }

        return $this->redirectToRoute('employee_salary_index',['id' => $employee->getId()]);
    }

    /**
     * @Route("/employee/salary/delete/{id}",name="employee_salary_delete")
     * @param SalaryPayment $salary
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function deleteSalary(SalaryPayment $salary,
                                     EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->setting->getWithHrm() ||
            !$this->appExtension->moduleExists(ModuleConstant::MODULES['hrm'])){
            throw new NotFoundHttpException("this ressource don't exists");
        }

        $employeeId = $salary->getEmployee()->getId();
        $entityManager->remove($salary);
        $entityManager->flush();

        return $this->redirectToRoute('employee_salary_index',['id' => $employeeId]);

    }

}
