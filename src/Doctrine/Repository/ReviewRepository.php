<?php

declare(strict_types=1);

namespace App\Doctrine\Repository;

use App\Model\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;

/**
 * @extends ServiceEntityRepository<Review>
 */
final class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findByUserAndVideoGame(User $user, VideoGame $videoGame): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.videoGame = :videoGame')
            ->setParameter('user', $user)
            ->setParameter('videoGame', $videoGame)
            ->getQuery()
            ->getOneOrNullResult();
    }
}