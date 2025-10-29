<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function getAllNotes(): array {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.bottle', 'b')    
            ->addSelect('b')  
            ->orderBy('n.creation_date', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function getNoteById(int $id): array {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.bottle', 'b')    
            ->addSelect('b')  
            ->andWhere('n.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }
}
