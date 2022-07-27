<?php

namespace App\Repository;

use App\Entity\ProductStock;
use App\Entity\Setting;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method ProductStock|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductStock|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductStock[]    findAll()
 * @method ProductStock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductStockRepository extends ServiceEntityRepository
{
    /**
     * @var Setting
     */
    private $setting;

    public function __construct(ManagerRegistry $registry, RequestStack $requestStack)
    {
        parent::__construct($registry, ProductStock::class);

        $this->setting = $requestStack->getSession()->get('setting');
    }

    public function getAllNotWithDraw($interval){

        $timestampDayExpiration = time() + $interval * 24 * 3600;

        $dateExpiration = \date('Y-m-d',$timestampDayExpiration);

        return $this->createQueryBuilder('pst')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true')
            ->andWhere('pst.withdraw = false')
            ->andWhere('pst.expirationDate is not null')
            ->andWhere('DATE(pst.expirationDate) >= DATE(:today)')
            ->andWhere('DATE(pst.expirationDate) <= DATE(:end)')
            ->setParameter('today', new DateTime())
            ->setParameter('end', $dateExpiration)
            ->getQuery()
            ->getResult();
    }

    public function getOutdated($withdraw){

        $q1 = $this->createQueryBuilder('pst')
            ->innerJoin('pst.stock','s')
            ->leftJoin('pst.productStockSales','pss')
            ->where('pss.id is null')
            ->andwhere('s.status = true')
            ->andWhere('pst.withdraw = :withdraw')
            ->andWhere('pst.expirationDate is not null')
            ->andWhere('DATE(pst.expirationDate) <= DATE(:today)')
            ->setParameter('today', new DateTime())
            ->setParameter('withdraw',$withdraw)
            //->having('pss is null')
            ->getQuery()
            ->getResult();

        $q2 = $this->createQueryBuilder('pst')
            ->innerJoin('pst.stock','s')
            ->innerJoin('pst.productStockSales','pss')
            ->where('s.status = true')
            ->andWhere('pst.withdraw = :withdraw')
            ->andWhere('pst.expirationDate is not null')
            ->andWhere('DATE(pst.expirationDate) <= DATE(:today)')
            ->setParameter('today', new DateTime())
            ->setParameter('withdraw',$withdraw)
            ->having('pst.qty > SUM(pss.qty)')
            ->getQuery()
            ->getResult();

        return array_merge($q1,$q2);
    }

    public function getByproductWithExpiration($product,$nbStocks = 1){
        return $this->createQueryBuilder('pst')
            ->innerJoin('pst.product','p')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true')
            ->andWhere('p = :product')
            ->andWhere('pst.withdraw = false')
            ->andWhere('pst.expirationDate is not null')
            ->setParameter('product',$product)
            ->setMaxResults($nbStocks)
            ->getQuery()
            ->getResult();
    }

    public function getByproduct($product,$nbStocks = 1){
        $qb = $this->createQueryBuilder('pst')
            ->innerJoin('pst.product','p')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true')
            ->andWhere('pst.withdraw = false')
            ->andWhere('p = :product')
            ->setParameter('product',$product);
        if ($this->setting->getWithExpiration() &&
            $this->getByproductWithExpiration($product,$nbStocks) !== null){
            $qb->orderBy('pst.expirationDate','ASC')
                ->addOrderBy('pst.id','DESC');
        }else{
            $qb->orderBy('pst.id','DESC');
        }

        return $qb->setMaxResults($nbStocks)
        ->getQuery()
        ->getResult();
    }

    public function countByproduct($product){
        try {
            return $this->createQueryBuilder('pst')
                ->select('SUM(pst.qty)')
                ->innerJoin('pst.product','p')
                ->innerJoin('pst.stock','s')
                ->where('p = :product')
                ->andWhere('s.status = true')
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
        return $this->createQueryBuilder('pst')
            ->select('p.id','SUM(pst.qty) as qty')
            ->innerJoin('pst.product','p')
            ->innerJoin('pst.stock','s')
            ->where('s.status = true')
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();
    }

    public function getAmountByStock($stock){
        try {
            return $this->createQueryBuilder('st')
                ->select('SUM(st.subtotal)')
                ->innerJoin('st.stock','s')
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
        return $this->createQueryBuilder('st')
            ->select('s.id','SUM(st.subtotal) as amount')
            ->innerJoin('st.stock','s')
            ->groupBy('s.id')
            ->getQuery()
            ->getScalarResult();
    }

    public function nbByproductAndPeriodDate($start,$end,$product=null)
    {
        $qb = $this->createQueryBuilder('pst')
            ->select( 'p.id','SUM(pst.qty) as qty')
            ->innerJoin('pst.stock','s')
            ->innerJoin('pst.product','p')
            ->where('DATE(s.deliveryDate) >= DATE(:start)')
            ->andWhere('DATE(s.deliveryDate) <= DATE(:end)')
            ->andWhere('s.status = true')
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
        $qb = $this->createQueryBuilder('pst')
            ->select( 'p.id','SUM(pst.qty) as qty')
            ->innerJoin('pst.product','p')
            ->innerJoin('pst.stock','s')
            ->where('DATE(s.deliveryDate) < DATE(:start)')
            ->andWhere('s.status = true')
            ->setParameter('start',  $start);

        if ($product !== null){
            $qb->andWhere('p = :product')
                ->setParameter('product', $product);
        }

        return $qb->groupBy('p.id')
            ->orderBy('p.id')
            ->getQuery()->getResult();
    }

    public function findproductStockByPeriod($start,$end,$product=null): ?array
    {

        $qb = $this->createQueryBuilder('pst')
            ->innerJoin('pst.stock','s')
            ->where('DATE(s.deliveryDate) >= DATE(:start)')
            ->andWhere('s.status = true')
            ->andWhere('DATE(s.deliveryDate) <= DATE(:end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.deliveryDate','DESC');
        if ($product !== null){
            $qb->andWhere('pst.product = :product')
                ->setParameter('product',$product);
        }
        return $qb->getQuery()
            ->getResult();
    }

    public function findProductStockByGroupPeriod($start,$end,$product=null): ?array
    {

        $qb = $this->createQueryBuilder('pst')
            ->select( 'pst.','pst.','psa.unitPrice ','SUM(pss.qty) as qty',
                'SUM(psa.profit) as profit','SUM(psa.subtotal) as subtotal')
            ->innerJoin('pst.stock','st')
            ->leftJoin('st.supplier','su')
            ->innerJoin('pst.productStockSales','pss')
            ->innerJoin('pss.productSale','psa')
            ->innerJoin('psa.sale','sa')
            ->where('DATE(sa.addDate) >= DATE(:start)')
            ->andWhere('DATE(sa.addDate) <= DATE(:end)')
            ->andWhere('sa.deleted = false')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('sa.addDate','DESC');
        if ($product !== null){
            $qb->andWhere('psa.product = :product')
                ->setParameter('product',$product);
        }
        return $qb->groupBy('addDate')
            ->addGroupBy('su')
            ->getQuery()
            ->getResult();
    }
}
