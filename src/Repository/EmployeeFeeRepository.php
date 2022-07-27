<?php

namespace App\Repository;

use App\Entity\EmployeeFee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmployeeFee|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeFee|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeFee[]    findAll()
 * @method EmployeeFee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeFeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeFee::class);
    }

    public function groupByPeriodDate($start,$end,$employee=null): array
    {
        $qb = $this->createQueryBuilder('ef')
            ->where('DATE(ef.addDate) >= :start')
            ->andWhere('DATE(ef.addDate) <= :end')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($employee !== null){
            $qb->innerJoin('ef.employee','e')
                ->andWhere('e = :employee')
                ->setParameter('employee', $employee);
        }

        return $qb->getQuery()->getResult();
    }
}
