<?php

namespace App\Entity;

use App\Repository\GrowSystemCompatibilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrowSystemCompatibilityRepository::class)]
#[ORM\UniqueConstraint(fields: ['plant', 'growSystem'])]
class GrowSystemCompatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'growSystemCompatibilities')]
    #[ORM\JoinColumn(nullable: false)]
    private Plant $plant;

    #[ORM\ManyToOne(inversedBy: 'compatibilities')]
    #[ORM\JoinColumn(nullable: false)]
    private GrowSystem $growSystem;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int { return $this->id; }

    public function getPlant(): Plant { return $this->plant; }
    public function setPlant(Plant $plant): static { $this->plant = $plant; return $this; }

    public function getGrowSystem(): GrowSystem { return $this->growSystem; }
    public function setGrowSystem(GrowSystem $growSystem): static { $this->growSystem = $growSystem; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
}
