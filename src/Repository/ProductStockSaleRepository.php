<?php

namespace App\Repository;

use App\Entity\ProductStockSale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductStockSale|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductStockSale|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductStockSale[]    findAll()
 * @method ProductStockSale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductStockSaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductStockSale::class);
    }

    public function countByproductStock($productStock){
        try {
            return $this->createQueryBuilder('pss')
                ->select('SUM(pss.qty)')
                ->innerJoin('pss.productStock','pst')
                ->innerJoin('pss.productSale','psa')
                ->innerJoin('psa.sale','s')
                ->where('s.deleted = false')
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

    public function findByProductStock($productStock){
         $qb = $this->createQueryBuilder('pss')
            ->select('c.id','c.name','c.type','pst.id as psid','SUM(pss.qty) as qtySold', 'SUM(psa.subtotal) as amount')
            ->innerJoin('pss.productStock','pst')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.sale','s')
            ->innerJoin('s.customer','c')
            ->where('s.deleted = false')
            ->andWhere('pst = :productStock')
            ->setParameter('productStock', $productStock);

        return $qb->groupBy('c.id')->getQuery()->getResult();
    }

    public function findByProductStockIsNull($productStock){
        $qb = $this->createQueryBuilder('pss')
            ->select('pst.id as psid','SUM(pss.qty) as qtySold',
                'SUM(psa.subtotal) as amount')
            ->innerJoin('pss.productStock','pst')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.sale','s')
            ->leftJoin('s.customer','c')
            ->where('s.deleted = false')
            ->andWhere('pst = :productStock')
            ->andWhere('c is null')
            ->setParameter('productStock', $productStock);

        return $qb->groupBy('c')->getQuery()->getResult();
    }

}
