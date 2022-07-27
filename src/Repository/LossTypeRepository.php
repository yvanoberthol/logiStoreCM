<?php

namespace App\Repository;

use App\Entity\LossType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LossType|null find($id, $lockMode = null, $lockVersion = null)
 * @method LossType|null findOneBy(array $criteria, array $orderBy = null)
 * @method LossType[]    findAll()
 * @method LossType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LossTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LossType::class);
    }
}
