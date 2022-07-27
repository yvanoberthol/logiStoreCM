<?php

namespace App\Repository;

use App\Entity\PageSize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageSize|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageSize|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageSize[]    findAll()
 * @method PageSize[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageSizeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageSize::class);
    }

    // /**
    //  * @return PageSize[] Returns an array of PageSize objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PageSize
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
