<?php


namespace App\Service;


use App\Entity\Attendance;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AttendanceRepository;
use App\Util\AttendanceStatusConstant;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class EmployeeService
{

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var AttendanceRepository
     */
    private $attendanceRepository;


    /**
     * ProductService constructor.
     * @param UserRepository $userRepository
     * @param AttendanceRepository $attendanceRepository
     */
    public function __construct(UserRepository $userRepository, AttendanceRepository $attendanceRepository)
    {
        $this->userRepository = $userRepository;
        $this->attendanceRepository = $attendanceRepository;
    }

    public function getReportAttendance($month, $year): array {


        $employees = $this->userRepository->findEmployees();


        $sattendances = [];
        foreach ($employees as $employee){
            $sattendances[] = $this->getAttendanceLine($month, $year, $employee);
        }

        return $sattendances;
    }

    private function getAttendanceLine($month, $year,User $employee): array {

        $attendances = $this->attendanceRepository
            ->findByMonthYear($month,$year,$employee);

        $absentCount = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'A';
            }));

        $lateCount = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'LA';
            }));

        $holidayCount = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'H';
            }));

        $leaveCount = count(array_filter($attendances,
            static function(Attendance $attendance){
                return strtoupper($attendance->getStatus()) === 'LE';
            }));

        $nbDaysOfMonth = cal_days_in_month(1,(int)$month,(int)$year);
        $presentCount = $nbDaysOfMonth - count($attendances);

        return [
            'id' => $employee->getId(),
            'name' => $employee->getName(),
            'presentCount' => $presentCount,
            'absentCount' => $absentCount,
            'lateCount' => $lateCount,
            'holidayCount' => $holidayCount,
            'leaveCount' => $leaveCount,
        ];
    }

}
