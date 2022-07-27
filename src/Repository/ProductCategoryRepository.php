<?php

namespace App\Repository;

use App\Entity\ProductCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCategory[]    findAll()
 * @method ProductCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    public function getByPeriodDate($start,$end): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id','c.name','SUM(ps.qty) as qtySold','SUM(ps.profit) as profit',
                'SUM(ps.subtotal) as amount')
            ->innerJoin('c.products','p')
            ->innerJoin('p.productSales','ps')
            ->innerJoin('ps.sale','s')
            ->where('s.deleted = false')
            ->andWhere('DATE(s.addDate) >= DATE(:start)')
            ->andWhere('DATE(s.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('c.id')
            ->having('SUM(ps.qty) >= 0')
            ->orderBy('qtySold','DESC')
            //->orderBy('amount','DESC')
            ->getQuery()->getResult();
    }

}
