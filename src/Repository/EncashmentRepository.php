<?php

namespace App\Repository;

use App\Entity\Encashment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Encashment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Encashment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Encashment[]    findAll()
 * @method Encashment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EncashmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Encashment::class);
    }

    public function findByDate($value, $employee=null)
    {
        try {
            $qb = $this->createQueryBuilder('e')
                ->where('DATE(e.date) = DATE(:addDate)')
                ->setParameter('addDate', $value);
            if ($employee !== null){
                $qb->innerJoin('e.employee','emp')
                    ->andWhere('emp = :employee')
                    ->setParameter('employee', $employee);
            }

            return $qb->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findByPeriod($start,$end, $employee=null)
    {
        $qb = $this->createQueryBuilder('e')
            ->where('DATE(e.date) >= DATE(:start)')
            ->andWhere('DATE(e.date) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($employee !== null){
            $qb->innerJoin('e.employee','emp')
                ->andWhere('emp = :employee')
                ->setParameter('employee', $employee);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
