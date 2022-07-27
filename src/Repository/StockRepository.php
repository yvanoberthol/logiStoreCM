<?php

namespace App\Repository;

use App\Entity\Stock;
use App\Util\RoleConstant;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stock|null find($id, $lockMode = null, $lockSersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function groupByDate(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DATE(s.addDate) as date','count(s) as nbStocks',
                'sum(s.amount) as amount')
            ->where('s.status = true');

        return $qb
            ->groupBy('date')
            ->orderBy('date')
            ->getQuery()->getArrayResult();
    }


    public function countAll()
    {
        try {
            return $this->createQueryBuilder('s')
                ->select('count(s)')
                ->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function findStockByPeriod($start,$end,$product=null)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('DATE(s.addDate) >= DATE(:start)')
            ->andWhere('DATE(s.addDate) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.addDate','DESC');
        if ($product !== null){
            $qb->andWhere('s.product = :product')
                ->setParameter('product',$product);
        }
        return $qb->getQuery()
            ->getResult();
    }

    public function findByPeriodSupplier($start,$end,$supplier)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.status = true')
            ->andWhere('DATE(s.addDate) >= DATE(:start)')
            ->andWhere('DATE(s.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);


        if ($supplier !== null){
            $qb->innerJoin('s.supplier','su')
                ->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }

        return $qb->orderBy('s.addDate', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function groupByDateSupplier($supplier): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DATE(s.addDate) as date','count(s) as nbStocks',
                'sum(s.amount) as amount')
            ->where('s.status = true');
        if ($supplier !== null){
            $qb->innerJoin('s.supplier','su')
                ->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }
        return $qb
            ->groupBy('date')
            ->orderBy('date')
            ->getQuery()->getArrayResult();
    }

    public function groupByYearSupplier($supplier): array
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) as nbStocks', 'sum(s.amount) as amount',
                'YEAR(s.addDate) as year')
            ->innerJoin('s.supplier','su')
            ->where('s.status = true')
            ->andWhere('su = :supplier')
            ->setParameter('supplier', $supplier)
            ->groupBy('year')->orderBy('year')
            ->getQuery()->getArrayResult();
    }

    public function groupByPeriodDateSupplier($start,$end,$supplier): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DATE(s.addDate) as date', 'count(s) as nbStocks',
                'sum(s.amount) as amount')
            ->where('s.status = true')
            ->andWhere('DATE(s.addDate) >= :start')
            ->andWhere('DATE(s.addDate) <= :end')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($supplier !== null){
            $qb->innerJoin('s.supplier','su')
                ->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }

        return $qb->groupBy('date')->getQuery()->getResult();
    }

    public function getStockByYearSupplier($year,$supplier= null): ?array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DATE(s.addDate) as date','count(s) as nbStocks',
                'sum(s.amount) as amount')
            ->where('s.status = true')
            ->andWhere('YEAR(s.addDate) = :year')
            ->setParameter('year', $year);
        if ($supplier !== null){
            $qb->innerJoin('s.supplier','su')
                ->andWhere('su = :supplier')
                ->setParameter('supplier', $supplier);
        }
        return $qb->groupBy('date')->orderBy('date')
            ->getQuery()->getArrayResult();
    }

    public function countAllBySupplier($supplier = null)
    {
        try {
            $qb = $this->createQueryBuilder('s')
                ->select('count(s)')
                ->where('s.status = true');
            if ($supplier !== null){
                $qb->innerJoin('s.supplier','su')
                    ->andWhere('su = :supplier')
                    ->setParameter('supplier', $supplier);
            }
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }
}
