<?php

namespace App\Repository;

use App\Entity\StockFee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockFee>
 *
 * @method StockFee|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockFee|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockFee[]    findAll()
 * @method StockFee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockFeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockFee::class);
    }

    public function add(StockFee $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StockFee $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAmountByStock($stock){
        try {
            return $this->createQueryBuilder('stf')
                ->select('SUM(stf.amount)')
                ->innerJoin('stf.stock','s')
                ->andWhere('s = :stock')
                ->setParameter('stock', $stock)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0.0;
        } catch (NonUniqueResultException $e) {
            return 0.0;
        }
    }

    public function getAmountByStocks(): array
    {
        return $this->createQueryBuilder('stf')
            ->select('s.id','SUM(stf.amount) as amount')
            ->innerJoin('stf.stock','s')
            ->groupBy('s.id')
            ->getQuery()
            ->getScalarResult();
    }

}
