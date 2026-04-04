<?php

namespace App\Repository;

use App\Entity\GrowSystemCompatibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GrowSystemCompatibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GrowSystemCompatibility::class);
    }
}
