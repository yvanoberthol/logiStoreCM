<?php

namespace App\Repository;

use App\Entity\Loss;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Loss|null find($id, $lockMode = null, $lockVersion = null)
 * @method Loss|null findOneBy(array $criteria, array $orderBy = null)
 * @method Loss[]    findAll()
 * @method Loss[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LossRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loss::class);
    }

    public function countByproduct($product){
        try {
            return $this->createQueryBuilder('l')
                ->select('SUM(l.qty)')
                ->innerJoin('l.productStock','st')
                ->innerJoin('st.product','p')
                ->where('p = :product')
                ->setParameter('product', $product)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function countByproductStock($productStock){
        try {
            return $this->createQueryBuilder('l')
                ->select('SUM(l.qty)')
                ->innerJoin('l.productStock','pst')
                ->where('pst = :productStock')
                ->setParameter('productStock', $productStock)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function countByproducts(){
        return $this->createQueryBuilder('l')
            ->select('p.id','SUM(l.qty) as qty')
            ->innerJoin('l.productStock','st')
            ->innerJoin('st.product','p')
            ->groupBy('p.id')
            ->getQuery()
            ->getScalarResult();
}

    public function findLossByMonth($month,$year): ?array
    {
        return $this->createQueryBuilder('l')
            ->where('MONTH(l.addDate) = :month')
            ->andWhere('YEAR(l.addDate) = :year')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->orderBy('l.addDate','DESC')
            ->getQuery()
            ->getResult();
    }

    public function nbByproductAndPeriodDate($start,$end,$product=null)
    {
        $qb = $this->createQueryBuilder('l')
            ->select( 'm.id','SUM(l.qty) as qty')
            ->innerJoin('l.productStock','mst')
            ->innerJoin('mst.product','m')
            ->where('DATE(l.addDate) >= DATE(:start)')
            ->andWhere('DATE(l.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        if ($product !== null){
            $qb->andWhere('m = :product')
                ->setParameter('product', $product);
        }

        return $qb->groupBy('m.id')
            ->orderBy('m.id')->getQuery()->getResult();
    }

    public function nbByproductAndBeforePeriodDate($start,$product=null)
    {
        $qb = $this->createQueryBuilder('l')
            ->select( 'p.id','SUM(l.qty) as qty')
            ->innerJoin('l.productStock','pst')
            ->innerJoin('pst.product','p')
            ->where('DATE(l.addDate) < DATE(:start)')
            ->setParameter('start',  $start);

        if ($product !== null){
            $qb->andWhere('p = :product')
                ->setParameter('product', $product);
        }

        return $qb->groupBy('p.id')
            ->orderBy('p.id')->getQuery()->getResult();
    }

    public function countAll()
    {
        try {
            return $this->createQueryBuilder('l')
                ->select('count(l)')
                ->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    public function findLossByPeriod($start, $end,$product=null)
    {
        $qb = $this->createQueryBuilder('l')
            ->where('DATE(l.addDate) >= DATE(:start)')
            ->andWhere('DATE(l.addDate) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('l.addDate','DESC');

        if ($product !== null){
            $qb->innerJoin('l.productStock','mst')
                ->innerJoin('mst.product','m')
                ->andWhere('m = :product')
                ->setParameter('product',$product);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
