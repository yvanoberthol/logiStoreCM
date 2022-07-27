<?php

namespace App\Repository;

use App\Entity\Adjustment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Adjustment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Adjustment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Adjustment[]    findAll()
 * @method Adjustment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdjustmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adjustment::class);
    }

    public function findAdjustmentByPeriod($start,$end): ?array
    {

        $qb = $this->createQueryBuilder('a')
            ->where('DATE(a.addDate) >= DATE(:start)')
            ->andWhere('DATE(a.addDate) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return $qb->orderBy('a.addDate','DESC')->getQuery()
            ->getResult();
    }

    public function findByPeriodUser($start,$end,$value): array
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.recorder','u')
            ->andWhere('DATE(a.addDate) >= DATE(:start)')
            ->andWhere('DATE(a.addDate) <= DATE(:end)')
            ->andWhere('u = :user')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end)
            ->setParameter('user', $value);

        return $qb
            ->orderBy('a.addDate', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function countAll($user = null)
    {
        try {
            $qb = $this->createQueryBuilder('a')
                ->select('count(a)');
            if ($user !== null){
                $qb->innerJoin('a.recorder','u')
                    ->andWhere('u = :user')
                    ->setParameter('user', $user);
            }
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }
}
