<?php

namespace App\Repository;

use App\Entity\ProductStockReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductStockReturn|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductStockReturn|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductStockReturn[]    findAll()
 * @method ProductStockReturn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductStockReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductStockReturn::class);
    }

    public function countByproduct($product){
        try {
            return $this->createQueryBuilder('psr')
                ->select('SUM(psr.qty)')
                ->innerJoin('psr.productStock','pst')
                ->innerJoin('pst.stock','s')
                ->innerJoin('pst.product','p')
                ->where('s.status = true')
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
            ->innerJoin('psr.productStock','pst')
            ->innerJoin('pst.product','p')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true')
            ->groupBy('p.id')
            ->getQuery()
            ->getScalarResult();
    }

    public function countByproductStock($productStock){
        try {
            return $this->createQueryBuilder('psr')
                ->select('SUM(psr.qty)')
                ->innerJoin('psr.productStock','pst')
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

    public function findProductStockReturnByPeriod($start,$end,$product=null,$employee=null): ?array
    {

        $qb = $this->createQueryBuilder('psr')
            ->innerJoin('psr.productStock','pst')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true')
            ->where('DATE(psr.date) >= DATE(:start)')
            ->andWhere('DATE(psr.date) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('psr.date','DESC');
        if ($product !== null){
            $qb->andWhere('pst.product = :product')
                ->setParameter('product',$product);
        }

        if ($employee !== null){
            $qb->andWhere('psr.recorder = :recorder')
                ->setParameter('recorder',$employee);
        }
        return $qb->getQuery()
            ->getResult();
    }

    public function findProductStockReturnByGroup($start,$end, $product): ?array
    {
        $qb = $this->createQueryBuilder('psr')
            ->select('s.id','sup.name as supplier','s.deliveryDate as addDate','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStock','pst')
            ->innerJoin('pst.product','p')
            ->innerJoin('pst.stock','s')
            ->leftJoin('s.supplier','sup')
            ->where('s.status = true')
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

    public function nbByproductAndPeriodDate($start,$end,$product=null)
    {
        $qb = $this->createQueryBuilder('psr')
            ->select( 'p.id','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStock','pst')
            ->innerJoin('pst.stock','s')
            ->innerJoin('pst.product','p')
            ->where('s.status = true')
            ->andWhere('DATE(psr.date) >= DATE(:start)')
            ->andWhere('DATE(psr.date) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($product !== null){
            $qb->andWhere('p = :product')
                ->setParameter('product', $product);
        }

        return $qb->groupBy('p.id')
            ->orderBy('p.id')
            ->getQuery()->getResult();
    }

    public function nbByproductAndBeforePeriodDate($start,$product=null):array
    {
        $qb = $this->createQueryBuilder('psr')
            ->select( 'p.id','SUM(psr.qty) as qty')
            ->innerJoin('psr.productStock','pst')
            ->innerJoin('pst.stock','s')
            ->innerJoin('pst.product','p')
            ->where('s.status = true')
            ->andWhere('DATE(psr.date) < DATE(:start)')
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
