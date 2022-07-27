<?php

namespace App\Repository;

use App\Entity\ProductPackaging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductPackaging|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductPackaging|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductPackaging[]    findAll()
 * @method ProductPackaging[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductPackagingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPackaging::class);
    }
}
