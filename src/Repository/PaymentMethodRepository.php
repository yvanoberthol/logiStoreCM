<?php

namespace App\Repository;

use App\Entity\PaymentMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PaymentMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentMethod[]    findAll()
 * @method PaymentMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

    public function qbFindActive($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $value);
    }

    public function findByPeriod($start,$end,$recorder=null): ?array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id','p.name','COUNT(s) as nbSales',
                'SUM(s.amount) as amount');
        $leftJoinSuit = '';
        if ($recorder !== null){
            $leftJoinSuit = ' and s.recorder = :recorder';
        }

        $qb->leftJoin('p.sales','s',Join::WITH,
            '(TIMESTAMP(s.addDate) >= TIMESTAMP(:start) and s.deleted = false and TIMESTAMP(s.addDate) <= TIMESTAMP(:end))'.$leftJoinSuit)
        ->setParameter('start', $start)
        ->setParameter('end', $end);
        if($recorder !== null){
            $qb->setParameter('recorder', $recorder);
        }

        return $qb->groupBy('p.id')
            ->having('COUNT(s) >= 0')
            ->orderBy('nbSales','DESC')
            ->getQuery()
            ->getResult();

    }

}
