<?php

namespace App\Repository;

use App\Entity\Product;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function getWithBarCode(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.qrCode is not null')
            ->getQuery()->getResult();
    }

    public function getBegin(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name like :name')
            ->setParameter('name', '%'.$name.'%')
            ->getQuery()->getResult();
    }

    public function countAll(){
        try {
            return $this->createQueryBuilder('p')
                ->select('count(p)')
                ->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }
    public function getByNameAndCategory($name,$category): ?Product
    {
        try {
            return $this->createQueryBuilder('p')
                ->select('p')
                ->innerJoin('p.category', 'c')
                ->where('p.name = :name')
                ->andWhere('c = :category')
                ->setParameter('name', $name)
                ->setParameter('category', $category)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function saleByPeriodDate($start,$end,$user=null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(psa.qty) as qtySold',
                'sum(psa.subtotal) as amount')
            ->innerJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s')
            ->where('s.deleted = false')
            ->andWhere('DATE(s.addDate) >= DATE(:start)')
            ->andWhere('DATE(s.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($user !== null){
            $qb->innerJoin('s.recorder','u')
                ->andWhere('u = :user')
                ->setParameter('user', $user);
        }

        return $qb->groupBy('p.id')
            ->orderBy('qtySold')
            ->getQuery()->getResult();
    }

    public function saleByCustomer($customer,$start=null,$end=null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(psa.qty) as qtySold',
                'sum(psa.subtotal) as amount')
            ->innerJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s')
            ->where('s.deleted = false');

        if ($start !== null) {
            $qb->andWhere('DATE(s.addDate) >= DATE(:start)')
                ->setParameter('start',  $start);
        }

        if ($end !== null) {
            $qb->andWhere('DATE(s.addDate) <= DATE(:end)')
                ->setParameter('end',  $end);
        }

        $qb->innerJoin('s.customer','c')
            ->andWhere('c = :customer')
            ->setParameter('customer', $customer);

        return $qb->groupBy('p.id')
            ->orderBy('qtySold')
            ->getQuery()->getResult();
    }

    public function stockBySupplier($supplier,$start=null,$end=null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(pst.qty) as qtyOrdered',
                'sum(pst.subtotal) as amount')
            ->innerJoin('p.productStocks','pst')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true');

        if ($start !== null) {
            $qb->andWhere('DATE(s.addDate) >= DATE(:start)')
                ->setParameter('start',  $start);
        }

        if ($end !== null) {
            $qb->andWhere('DATE(s.addDate) <= DATE(:end)')
                ->setParameter('end',  $end);
        }

        $qb->innerJoin('s.supplier','su')
            ->andWhere('su = :supplier')
            ->setParameter('supplier', $supplier);

        return $qb->groupBy('p.id')
            ->orderBy('qtyOrdered')
            ->getQuery()->getResult();
    }

    public function getByPeriodDate($start,$end,$user=null,$customer=null,$category=null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(psa.qty) as qtySold',
                'sum(psa.subtotal) as amount','sum(psa.profit) as profit')
            ->leftJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s',Join::WITH,
                's.deleted = false and DATE(s.addDate) >= DATE(:start) and DATE(s.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($user !== null){
            $qb->innerJoin('s.recorder','u')
                ->andWhere('u = :user')
                ->setParameter('user', $user);
        }

        if ($customer !== null){
            $qb->innerJoin('s.customer','c')
                ->andWhere('c = :customer')
                ->setParameter('customer', $customer);
        }

        if ($category!== null){
            $qb->innerJoin('p.category','cat')
                ->andWhere('cat = :category')
                ->setParameter('category', $category);
        }

        return $qb->groupBy('p.id')
            ->orderBy('qtySold','DESC')
            //->orderBy('amount','DESC')
            ->getQuery()->getResult();
    }

    public function getByAdjustmentPeriodDate($start,$end,$user=null,$category=null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(pa.qty) as qtyAdjusted', 'MIN(pa.newQty) as realQty')
            ->leftJoin('p.productAdjusts','pa')
            ->innerJoin('pa.adjustment','a',Join::WITH,
                'DATE(a.addDate) >= DATE(:start) and DATE(a.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($user !== null){
            $qb->innerJoin('a.recorder','u')
                ->andWhere('u = :user')
                ->setParameter('user', $user);
        }

        if ($category!== null){
            $qb->innerJoin('p.category','cat')
                ->andWhere('cat = :category')
                ->setParameter('category', $category);
        }

        return $qb->groupBy('p.id')
            ->orderBy('qtyAdjusted','DESC')
            ->getQuery()->getResult();
    }


    public function getStockByPeriodDate($start,$end,$user=null,$supplier=null,$category=null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(pst.qty) as qtyPurchase',
                'sum(pst.subtotal) as amount')
            ->leftJoin('p.productStocks','pst')
            ->innerJoin('pst.stock','s',Join::WITH,
                's.status = true and DATE(s.addDate) >= DATE(:start) and DATE(s.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($user !== null){
            $qb->innerJoin('s.recorder','u')
                ->andWhere('u = :user')
                ->setParameter('user', $user);
        }

        if ($supplier !== null){
            $qb->innerJoin('s.supplier','sp')
                ->andWhere('sp = :supplier')
                ->setParameter('supplier', $supplier);
        }

        if ($category!== null){
            $qb->innerJoin('p.category','cat')
                ->andWhere('cat = :category')
                ->setParameter('category', $category);
        }

        return $qb->groupBy('p.id')
            ->orderBy('qtyPurchase','DESC')
            //->orderBy('amount','DESC')
            ->getQuery()->getResult();
    }

    public function saleByproduct($month, $year): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'SUM(psa.qty) as qtySold',
                'SUM(psa.subtotal) as amount')
            ->innerJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s')
            ->where('s.deleted = false')
            ->andWhere('MONTH(s.addDate) = :month')
            ->andWhere('YEAR(s.addDate) = :year')
            ->setParameter('month',  $month)
            ->setParameter('year',  $year)
            ->groupBy('p.id')
            ->orderBy('qtySold')
            ->getQuery()->getResult();

    }

    public function getNew($interval): array
    {
        $timestampDayExpiration = $interval * 24 * 3600;

        return $this->createQueryBuilder('m')
            ->where('(TIMESTAMP(DATE(m.addDate))+:interval) <= TIMESTAMP(DATE(:end))')
            ->setParameter('end', new DateTime())
            ->setParameter('interval', $timestampDayExpiration)
            ->getQuery()->getResult();
    }

    public function getSaleReturnByPeriodDate($start,$end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(psr.qty) as qtyReturn',
                '(sum(psr.qty) * psa.unitPrice) as amount')
            ->innerJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s')
            ->innerJoin('psa.productStockSales','pss')
            ->innerJoin('pss.productSaleReturns','psr',Join::WITH,
                'DATE(psr.date) >= DATE(:start) and DATE(psr.date) <= DATE(:end)')
            ->where('s.deleted = false')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('p.id')
            ->orderBy('qtyReturn','DESC')
            ->getQuery()->getResult();
    }

    public function getSaleReturnStockableByPeriodDate($start,$end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(psr.qty) as qtyStockable',
                '(sum(psr.qty) * psa.unitPrice) as amount')
            ->innerJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s')
            ->innerJoin('psa.productStockSales','pss')
            ->innerJoin('pss.productSaleReturns','psr',Join::WITH,
                'DATE(psr.date) >= DATE(:start) and DATE(psr.date) <= DATE(:end)')
            ->where('s.deleted = false')
            ->andWhere('psr.stockable = true')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('p.id')
            ->orderBy('qtyStockable','DESC')
            ->getQuery()->getResult();
    }

    public function getSaleReturnRepayByPeriodDate($start,$end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p','sum(psr.qty) as qtyRepay','(sum(psr.qty) * psa.unitPrice) as amount')
            ->innerJoin('p.productSales','psa')
            ->innerJoin('psa.sale','s')
            ->innerJoin('psa.productStockSales','pss')
            ->innerJoin('pss.productSaleReturns','psr',Join::WITH,
                'DATE(psr.date) >= DATE(:start) and DATE(psr.date) <= DATE(:end)')
            ->where('s.deleted = false')
            ->andWhere('psr.repay = true')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('p.id')
            ->orderBy('qtyRepay','DESC')
            ->getQuery()->getResult();
    }


    public function getStockReturnByPeriodDate($start,$end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'SUM(psr.qty) as qtyReturn',
                '(sum(psr.qty) * pst.unitPrice) as amount')
            ->innerJoin('p.productStocks','pst')
            ->innerJoin('pst.stock','s')
            ->innerJoin('pst.productStockReturns','psr',Join::WITH,
                'DATE(psr.date) >= DATE(:start) and DATE(psr.date) <= DATE(:end)')
            ->where('s.status = true')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('p.id')
            ->orderBy('qtyReturn','DESC')
            ->getQuery()->getResult();
    }

    public function getStockReturnRepayByPeriodDate($start,$end)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p','sum(psr.qty) as qtyRepay','(sum(psr.qty) * pst.unitPrice) as amount')
            ->innerJoin('p.productStocks','pst')
            ->innerJoin('pst.stock','s')
            ->innerJoin('pst.productStockReturns','psr',Join::WITH,
                'DATE(psr.date) >= DATE(:start) and DATE(psr.date) <= DATE(:end)')
            ->where('s.status = true')
            ->andWhere('psr.repay = true')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('p.id')
            ->orderBy('qtyRepay','DESC')
            ->getQuery()->getResult();
    }

}
