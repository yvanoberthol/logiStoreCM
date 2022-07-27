<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findTransactionByPeriod($start,$end,$bank=null): ?array
    {

        $qb = $this->createQueryBuilder('t')
            ->where('DATE(t.date) >= DATE(:start)')
            ->andWhere('DATE(t.date) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.date','DESC');

        if ($bank !== null){
            $qb->innerJoin('t.bank','b')
                ->andWhere('b = :bank')
                ->setParameter('bank',$bank);
        }

        return $qb->getQuery()
            ->getResult();
    }

}
