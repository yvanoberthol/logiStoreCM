<?php

namespace App\Repository;

use App\Entity\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Connection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Connection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Connection[]    findAll()
 * @method Connection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connection::class);
    }

    public function findLastConnection($user): ?Connection
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.user', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByPeriodAndUser($start,$end,$user=null): array
    {
         $q = $this->createQueryBuilder('c')
             ->where('DATE(c.addDate) >= :start')
             ->andWhere('DATE(c.addDate) <= :end')
             ->setParameter('start',  $start)
             ->setParameter('end',  $end);

         if ($user !== null){
             $q->innerJoin('c.user', 'u')
             ->andWhere('u = :user')
             ->setParameter('user', $user);
         }

        return $q->orderBy('c.addDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
