<?php

namespace App\Repository;

use App\Entity\Reference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reference>
 */
class ReferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reference::class);
    }

    public function searchReference(string $query): array {
        return $this->createQueryBuilder('b')
        ->select('b.id, b.name')
        ->where('b.name LIKE :query')
        ->setParameter('query', '%' . $query . '%')
        ->orderBy('b.name', 'ASC')
        ->setMaxResults(12)
        ->getQuery()
        ->getArrayResult();
    }

    public function getReferenceById(int $id): array {
        return $this->createQueryBuilder('b')
            ->where('b.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function compareWithReference(array $appellations): array {
        return $this->createQueryBuilder('e')
            ->where('e.name IN (:names)')
            ->setParameter('names', $appellations)
            ->getQuery()->getResult();
    }
}
