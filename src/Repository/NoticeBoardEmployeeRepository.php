<?php

namespace App\Repository;

use App\Entity\NoticeBoardEmployee;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NoticeBoardEmployee|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoticeBoardEmployee|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoticeBoardEmployee[]    findAll()
 * @method NoticeBoardEmployee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoticeBoardEmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoticeBoardEmployee::class);
    }


    public function findByUser($employee,$statut)
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.employee','e')
            ->innerJoin('n.noticeBoard','nb')
            ->where('e = :employee')
            ->andWhere('n.seen = :statut')
            ->andWhere('DATE(:now) >= DATE(nb.start)')
            ->andWhere('DATE(:now) <= DATE(nb.end)')
            ->setParameter('employee', $employee)
            ->setParameter('statut', $statut)
            ->setParameter('now', new DateTime())
            ->orderBy('n.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

}
