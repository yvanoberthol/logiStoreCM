<?php

namespace App\Repository;

use App\Entity\ProductAdjust;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductAdjust|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductAdjust|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductAdjust[]    findAll()
 * @method ProductAdjust[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductAdjustRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductAdjust::class);
    }

    public function countByproduct($product){
        try {
            return $this->createQueryBuilder('pad')
                ->select('SUM(pad.qty)')
                ->innerJoin('pad.product','p')
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
        return $this->createQueryBuilder('pad')
            ->select('p.id','SUM(pad.qty) as qty')
            ->innerJoin('pad.product','p')
            ->groupBy('p.id')
            ->getQuery()
            ->getScalarResult();
    }

    public function nbByproductAndPeriodDate($start,$end,$product=null,$employee=null)
    {
        $qb = $this->createQueryBuilder('pad')
            ->select( 'p.id','SUM(pad.qty) as qty')
            ->innerJoin('pad.adjustment','a')
            ->innerJoin('pad.product','p')
            ->andWhere('DATE(a.addDate) >= DATE(:start)')
            ->andWhere('DATE(a.addDate) <= DATE(:end)')
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

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

    public function nbByproductAndBeforePeriodDate($start,$product=null)
    {
        $qb = $this->createQueryBuilder('pad')
            ->select( 'p.id','SUM(pad.qty) as qty')
            ->innerJoin('pad.product','p')
            ->innerJoin('pad.adjustment','a')
            ->andWhere('DATE(a.addDate) < DATE(:start)')
            ->setParameter('start',  $start);

        if ($product !== null){
            $qb->andWhere('p = :product')
                ->setParameter('product', $product);
        }

        return $qb->groupBy('p.id')
            ->orderBy('p.id')
            ->getQuery()->getResult();
    }

    public function findproductAdjustByPeriod($start,$end,$product=null): ?array
    {

        $qb = $this->createQueryBuilder('pad')
            ->innerJoin('pad.adjustment','a')
            ->where('DATE(a.addDate) >= DATE(:start)')
            ->andWhere('DATE(a.addDate) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.addDate','DESC');
        if ($product !== null){
            $qb->andWhere('pad.product = :product')
                ->setParameter('product',$product);
        }
        return $qb->getQuery()
            ->getResult();
    }
}
