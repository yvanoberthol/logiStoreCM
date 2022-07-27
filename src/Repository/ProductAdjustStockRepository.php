<?php

namespace App\Repository;

use App\Entity\ProductAdjustStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductAdjustStock|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductAdjustStock|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductAdjustStock[]    findAll()
 * @method ProductAdjustStock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductAdjustStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductAdjustStock::class);
    }

    public function countByproductStock($productStock){
        try {
            return $this->createQueryBuilder('pas')
                ->select('SUM(pas.qty)')
                ->innerJoin('pas.productStock','pst')
                ->innerJoin('pas.productAdjust','pad')
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
}
