<?php

namespace App\Repository;

use App\Entity\NoticeBoard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NoticeBoard|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoticeBoard|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoticeBoard[]    findAll()
 * @method NoticeBoard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoticeBoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoticeBoard::class);
    }
}
