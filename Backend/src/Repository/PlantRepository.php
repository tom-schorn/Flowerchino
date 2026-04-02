<?php

namespace App\Repository;

use App\Entity\Plant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
