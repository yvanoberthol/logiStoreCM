<?php

namespace App\Repository;

use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Supplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Supplier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Supplier[]    findAll()
 * @method Supplier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function getByPeriodDate($start,$end): array
    {
        $qb = $this->createQueryBuilder('sup')
            ->select('sup.id','sup.name','SUM(ps.qty) as qtyPurchase',
                'SUM(ps.subtotal) as amount','COUNT(s) as nbStocks')
            ->leftJoin('sup.stocks','s',Join::WITH,
                '(DATE(s.addDate) >= DATE(:start) and DATE(s.addDate) <= DATE(:end) and s.status = true)')
            ->leftJoin('s.productStocks','ps')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('sup.id')
            ->having('COUNT(s) >= 0')
            ->orderBy('qtyPurchase','DESC')
            //->orderBy('amount','DESC')
            ->getQuery()->getResult();
    }
}
