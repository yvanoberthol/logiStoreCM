<?php

namespace App\Repository;

use App\Entity\SalePayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SalePayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method SalePayment|null findOneBy(array $criteria, array $orderBy = null)
 * @method SalePayment[]    findAll()
 * @method SalePayment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SalePaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalePayment::class);
    }


    public function findByCustomer($customer)
    {
        return $this->createQueryBuilder('sp')
            ->innerJoin('sp.sale','s')
            ->innerJoin('s.customer','c')
            ->andWhere('c = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByPeriodDate($start,$end,$sale=null,$customer=null): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->innerJoin('sp.sale','s')
            ->innerJoin('s.customer','c')
            ->where('s.deleted = false')
            ->andWhere('DATE(sp.addDate) >= DATE(:start)')
            ->andWhere('DATE(sp.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end)
            ->orderBy('sp.addDate','DESC');

        if ($sale !== null){
            $qb->andWhere('s = :sale')
                ->setParameter('sale', $sale);
        }

        if ($customer !== null){
            $qb->andWhere('c = :customer')
                ->setParameter('customer', $customer);
        }

        return $qb->getQuery()->getResult();
    }

    public function groupByPeriodDate($start,$end,$sale=null,$customer=null): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('DATE(sp.addDate) as date','SUM(sp.amount) as amount',
                'c.id as customerId','c.name as customerName','r.name as recorderName')
            ->innerJoin('sp.sale','s')
            ->innerJoin('sp.recorder','r')
            ->innerJoin('s.customer','c')
            ->where('DATE(sp.addDate) >= DATE(:start)')
            ->andWhere('DATE(sp.addDate) <= DATE(:end)')
            ->andWhere('s.deleted = false')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($sale !== null){
            $qb->andWhere('s = :sale')
                ->setParameter('sale', $sale);
        }

        if ($customer !== null){
            $qb->andWhere('c = :customer')
                ->setParameter('customer', $customer);
        }

        return $qb->groupBy('date')->addGroupBy('customerId')
            ->getQuery()->getResult();
    }

    public function lastTenPayments($customer=null): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('DATE(sp.addDate) as date','SUM(sp.amount) as amount',
                'c.id as customerId','c.name as customerName','r.name as recorderName')
            ->innerJoin('sp.sale','s')
            ->innerJoin('sp.recorder','r')
            ->innerJoin('s.customer','c');

        if ($customer !== null){
            $qb->andWhere('c = :customer')
                ->setParameter('customer', $customer);
        }

        return $qb->groupBy('date')->addGroupBy('recorderName')
            ->orderBy('date','DESC')->setMaxResults(10)->getQuery()->getResult();
    }

}
