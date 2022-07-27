<?php

namespace App\Repository;

use App\Entity\ProductSaleReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductSaleReturn|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductSaleReturn|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductSaleReturn[]    findAll()
 * @method ProductSaleReturn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductSaleReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSaleReturn::class);
    }

    public function countByproduct($product){
        try {
            return $this->createQueryBuilder('psr')
                ->select('SUM(psr.qty)')
                ->innerJoin('psr.productStockSale','pss')
                ->innerJoin('pss.productStock','pst')
                ->innerJoin('pss.productSale','psa')
                ->innerJoin('psa.sale','s')
                ->innerJoin('pst.product','p')
                ->where('s.deleted = false')
                ->andWhere('psr.stockable = true')
                ->andWhere('p = :product')
                ->setParameter('product', $product)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function countByproducts(): array {
        return $this->createQueryBuilder('psr')
            ->select('p.id','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStockSale','pss')
            ->innerJoin('pss.productStock','pst')
            ->innerJoin('pst.product','p')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.sale','s')
            ->where('s.deleted = false')
            ->andWhere('psr.stockable = true')
            ->groupBy('p.id')
            ->getQuery()
            ->getScalarResult();
    }

    public function countByproductStock($productStock){
        try {
            return $this->createQueryBuilder('psr')
                ->select('SUM(psr.qty)')
                ->innerJoin('psr.productStockSale','pss')
                ->innerJoin('pss.productStock','pst')
                ->innerJoin('pss.productSale','psa')
                ->innerJoin('psa.sale','s')
                ->where('s.deleted = false')
                ->where('psr.stockable = true')
                ->andWhere('pst = :productStock')
                ->setParameter('productStock', $productStock)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function findProductSaleReturnByGroup($start,$end, $product)
    {
        $qb = $this->createQueryBuilder('psr')
            ->select('s.id','s.addDate as addDate','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStockSale','pss')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.product','p')
            ->innerJoin('psa.sale','s')
            ->where('s.deleted = false')
            ->andWhere('DATE(psr.date) >= DATE(:start)')
            ->andWhere('DATE(psr.date) <= DATE(:end)')
            ->andWhere('p = :product')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end)
            ->setParameter('product',  $product);

        return $qb->groupBy('s.id')
            ->orderBy('s.addDate','DESC')
            ->getQuery()->getResult();
    }

    public function nbByproductAndPeriodDate($start,$end,$product=null,$stockable=null,$employee=null)
    {
        $qb = $this->createQueryBuilder('psr')
            ->select( 'p.id','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStockSale','pss')
            ->innerJoin('pss.productStock','pst')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.sale','s')
            ->innerJoin('pst.product','p')
            ->where('s.deleted = false')
            ->andWhere('DATE(psr.date) >= DATE(:start)')
            ->andWhere('DATE(psr.date) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($stockable !== null){
            $qb->andWhere('psr.stockable = :stockable')
                ->setParameter('stockable', $stockable);
        }

        if ($product !== null){
            $qb->andWhere('p = :product')
                ->setParameter('product', $product);
        }

        if ($employee !== null){
            $qb->innerJoin('s.recorder','r')
                ->andWhere('r = :recorder')
                ->setParameter('recorder', $employee);
        }

        return $qb->groupBy('p.id')
            ->orderBy('p.id')
            ->getQuery()->getResult();
    }

    public function nbByproductAndBeforePeriodDate($start,$product=null,$stockable=null):array
    {
        $qb = $this->createQueryBuilder('psr')
            ->select( 'p.id','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStockSale','pss')
            ->innerJoin('pss.productStock','pst')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.sale','s')
            ->innerJoin('pst.product','p')
            ->where('s.deleted = false');
        if ($stockable !== null){
            $qb->andWhere('psr.stockable = :stockable')
                ->setParameter('stockable',  $stockable);
        }

        $qb->andWhere('DATE(psr.date) < DATE(:start)')
            ->setParameter('start',  $start);

        if ($product !== null){
            $qb->andWhere('p = :product')
                ->setParameter('product', $product);
        }

        return $qb->groupBy('p.id')
            ->orderBy('p.id')
            ->getQuery()->getResult();
    }
}
