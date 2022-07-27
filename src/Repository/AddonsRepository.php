<?php

namespace App\Repository;

use App\Entity\Addons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Addons|null find($id, $lockMode = null, $lockVersion = null)
 * @method Addons|null findOneBy(array $criteria, array $orderBy = null)
 * @method Addons[]    findAll()
 * @method Addons[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Addons::class);
    }

    // /**
    //  * @return Addons[] Returns an array of Addons objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Addons
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
