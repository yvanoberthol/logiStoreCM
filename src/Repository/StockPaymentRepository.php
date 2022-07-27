<?php

namespace App\Repository;

use App\Entity\StockPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StockPayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockPayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockPayment[]    findAll()
 * @method StockPayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockPayment::class);
    }

    public function findStockPaymentByPeriod($start,$end,$stock=null,$supplier=null): ?array
    {

        $qb = $this->createQueryBuilder('sp')
            ->innerJoin('sp.stock','s')
            ->innerJoin('s.supplier','su')
            ->where('s.status = true')
            ->where('DATE(sp.addDate) >= DATE(:start)')
            ->andWhere('DATE(sp.addDate) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('sp.addDate','DESC');

        if ($stock !== null){
            $qb->andWhere('s = :stock')
                ->setParameter('stock', $stock);
        }

        if ($supplier !== null){
            $qb->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function groupByPeriodDate($start,$end,$stock=null,$supplier=null): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('DATE(sp.addDate) as date','SUM(sp.amount) as amount',
                'su.id as supplierId','su.name as supplierName','r.name as recorderName')
            ->innerJoin('sp.stock','s')
            ->innerJoin('sp.recorder','r')
            ->innerJoin('s.supplier','su')
            ->where('s.status = true')
            ->andWhere('DATE(sp.addDate) >= DATE(:start)')
            ->andWhere('DATE(sp.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($stock !== null){
            $qb->andWhere('s = :stock')
                ->setParameter('stock', $stock);
        }

        if ($supplier !== null){
            $qb->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }

        return $qb->groupBy('date')->addGroupBy('supplierId')
            ->getQuery()->getResult();
    }

    public function lastTenPayments($supplier=null): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('DATE(sp.addDate) as date','SUM(sp.amount) as amount',
                'su.id as supplierId','su.name as supplierName','r.name as recorderName')
            ->innerJoin('sp.stock','s')
            ->innerJoin('sp.recorder','r')
            ->innerJoin('s.supplier','su')
            ->where('s.status = true');

        if ($supplier !== null){
            $qb->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }

        return $qb->groupBy('date')->addGroupBy('recorderName')
            ->orderBy('date','DESC')->setMaxResults(10)->getQuery()->getResult();
    }

}
