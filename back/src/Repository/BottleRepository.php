<?php

namespace App\Repository;

use App\Entity\Bottle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bottle>
 */
class BottleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bottle::class);
    }

    public function findWithPaginationAndSearch(
    int $limit,
    int $offset,
    string $search,
    array $filters = []
): array {
    $qb = $this->createQueryBuilder('b');

    // Appliquer la recherche uniquement si elle est non vide
    if ($search !== '') {
        // Si aucun filtre spécifique n'est fourni, rechercher dans les champs par défaut
        if (empty($filters)) {
            $filters = ['name', 'domaine'];
        }

        $orX = $qb->expr()->orX();

        foreach ($filters as $field) {
            $orX->add($qb->expr()->like("b.$field", ':search'));
        }

        $qb->andWhere($orX)
           ->setParameter('search', '%' . $search . '%');
    }

    // Cloner le query builder pour obtenir le nombre total
    $qbCount = clone $qb;
    $qbCount->select('COUNT(b.id)');
    $totalCount = (int) $qbCount->getQuery()->getSingleScalarResult();

    // Appliquer la pagination
    $qb->setFirstResult($offset)
       ->setMaxResults($limit)
       ->orderBy('b.name', 'ASC');

    $items = $qb->getQuery()->getResult();

    return [$items, $totalCount];
}

}
