<?php

namespace App\Repository;

use App\Entity\SalaryPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SalaryPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method SalaryPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method SalaryPayment[]    findAll()
 * @method SalaryPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SalaryPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalaryPayment::class);
    }


    public function findByMonthYear($month,$year,$employee=null)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.year = :year')
            ->setParameter('year', $year);

        if ($month !== '0'){
            $qb->andWhere('s.month = :month')
                ->setParameter('month', $month);
        }

        if ($employee !== null){
            $qb->andWhere('s.employee = :employee')
                ->setParameter('employee', $employee);
        }

        return $qb->orderBy('s.addDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByMonthYearReport($month,$year)
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('e.id as employeeId','e.name','sp.salary as salary',
                'SUM(sp.amount) as amount','(sp.salary - SUM(sp.amount)) as amountRemaining')
            ->innerJoin('sp.employee','e')
            ->where('sp.year = :year')
            ->andWhere('sp.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month);
        return $qb
            ->groupBy('e.id')
            ->getQuery()
            ->getResult()
            ;
    }
}
