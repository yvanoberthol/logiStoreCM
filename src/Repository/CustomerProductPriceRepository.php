<?php

namespace App\Repository;

use App\Entity\CustomerProductPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CustomerProductPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerProductPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerProductPrice[]    findAll()
 * @method CustomerProductPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerProductPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerProductPrice::class);
    }
}
