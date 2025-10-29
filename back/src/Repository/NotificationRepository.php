<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function getUnreadNotifications(string $type): array {
        return $this->createQueryBuilder('n')
            ->where('n.type = :type')
            ->andWhere('n.isClosedByUser = :false')
            ->setParameter('type', $type)
            ->setParameter('false', false)
            ->getQuery()->getArrayResult();
    }
}
