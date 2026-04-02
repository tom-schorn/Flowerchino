<?php

namespace App\Repository;

use App\Entity\Plant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class PlantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plant::class);
    }

    public function findByGbifKey(int $gbifKey): ?Plant
    {
        return $this->findOneBy(['gbifKey' => $gbifKey]);
    }

    public function findBySlug(string $slug): ?Plant
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByIpniId(string $ipniId): ?Plant
    {
        return $this->findOneBy(['ipniId' => $ipniId]);
    }

    /**
     * @return array{items: Plant[], total: int}
     */
    public function findPaginated(int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.canonicalName', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }

    /**
     * @return array{items: Plant[], total: int}
     */
    public function search(string $query, int $page, int $perPage): array
    {
        $term = '%' . $query . '%';

        $qb = $this->createQueryBuilder('p')
            ->where('p.canonicalName LIKE :term OR p.scientificName LIKE :term OR p.genus LIKE :term OR p.species LIKE :term')
            ->setParameter('term', $term)
            ->orderBy('p.canonicalName', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }
}
