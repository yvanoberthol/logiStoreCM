<?php

namespace App\Repository;

use App\Entity\Expense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Expense|null find($id, $lockMode = null, $lockVersion = null)
 * @method Expense|null findOneBy(array $criteria, array $orderBy = null)
 * @method Expense[]    findAll()
 * @method Expense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    public function findExpenseByPeriod($start,$end,$type=null): ?array
    {

        $qb = $this->createQueryBuilder('e')
            ->where('DATE(e.date) >= DATE(:start)')
            ->andWhere('DATE(e.date) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.date','DESC');
        if ($type !== null){
            $qb->innerJoin('e.type','type')
                ->andWhere('type.id = :type')
                ->setParameter('type',$type);
        }
        return $qb->getQuery()
            ->getResult();
    }
}
