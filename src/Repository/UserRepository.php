<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByNameOrEmail($value){
        try {
            return $this->createQueryBuilder('u')
                ->where('u.name = :value')
                ->orWhere('u.email = :value')
                ->setParameter('value', $value)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findUserByRole($value)
    {
        $q = $this->createQueryBuilder('u');
        return $q
            ->innerJoin('u.role','r')
            ->where('r.name = :value')
            ->setParameter('value', $value)
            ->getQuery()->getResult();

    }

    public function QbUserByRole($value)
    {

        return $this->createQueryBuilder('u')
            ->innerJoin('u.role','r')
            ->where('r.name = :value')
            ->setParameter('value', $value);
    }

    public function findEmployees()
    {
        $q = $this->createQueryBuilder('u')
            ->leftJoin('u.role','r')
            ->where("r is null")
            ->orWhere("r.name != 'ROLE_ADMIN'");

        return $q->getQuery()->getResult();
    }

    public function findWithRole()
    {
        $q = $this->createQueryBuilder('u')
            ->innerJoin('u.role','r')
            ->where("r is not null");

        return $q->getQuery()->getResult();
    }

    public function getSaleByMonth($month,$year): ?array {
        return $this->createQueryBuilder('u')
            ->select('u','count(v) as nbSales','sum(v.amount) as amountSold')
            ->innerJoin('u.role','r')
            ->leftJoin('u.sales','v')
            ->where('MONTH(v.addDate) = :month')
            ->andWhere('YEAR(v.addDate) = :year')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->groupBy('u.id')
            ->getQuery()->getArrayResult();
    }

    public function getSaleByYear($year): ?array {
        return $this->createQueryBuilder('u')
            ->select('u','sum(v.amount) as amountSold','count(v) as nbSales')
            ->innerJoin('u.role','r')
            ->leftJoin('u.sales','v')
            ->where('YEAR(v.addDate) = :year')
            ->setParameter('year', $year)
            ->groupBy('u.id')
            ->getQuery()->getArrayResult();
    }

    public function findByMonthYearReport($month,$year)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e','sp.salary as salary',
                'SUM(sp.amount) as amount',
                '(sp.salary - SUM(sp.amount)) as amountRemaining')
            ->leftJoin('e.role','r')
            ->leftJoin('e.salaryPayments','sp',Join::WITH,
                'sp.year = :year and sp.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->where("r is null")
            ->orWhere("r.name != 'ROLE_ADMIN'");
        return $qb
            ->groupBy('e.id')
            ->having('COUNT(sp) >= 0')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getByPeriodDate($start,$end): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id','u.name','COUNT(s) as nbSales','SUM(s.profit) as profit',
                'SUM(s.amount) as amount')
            ->leftJoin('u.sales','s',Join::WITH,
            '(DATE(s.addDate) >= DATE(:start) and s.deleted = false 
            and DATE(s.addDate) <= DATE(:end))')
            ->innerJoin('u.role','r')
            ->where("r is not null")
            ->andWhere("r.name != 'ROLE_ADMIN'")
            ->setParameter('start',  $start)
            ->setParameter('end',  $end);

        return $qb->groupBy('u.id')
            ->having('COUNT(s) >= 0')
            ->orderBy('nbSales','DESC')
            //->orderBy('amount','DESC')
            ->getQuery()->getResult();
    }

    /**
     * @param string $username
     * @return void
     *
     * @deprecated since Symfony 5.3, use loadUserByIdentifier() instead
     */
    public function loadUserByUsername(string $username)
    {
        try {
            return $this->createQueryBuilder('u')
                ->where('u.name = :value')
                ->orWhere('u.email = :value')
                ->setParameter('value', $username)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param string $username
     * @return void
     *
     */
    public function loadUserByIdentifier(string $username)
    {
        try {
            return $this->createQueryBuilder('u')
                ->where('u.name = :value')
                ->orWhere('u.email = :value')
                ->setParameter('value', $username)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method null loadUserByIdentifier(string $identifier)
    }
}
