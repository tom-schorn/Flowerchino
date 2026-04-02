<?php

namespace App\Entity;

use App\Repository\GrowSystemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrowSystemRepository::class)]
class GrowSystem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 20)]
    private string $type = 'hydroponic';

    #[ORM\OneToMany(mappedBy: 'growSystem', targetEntity: GrowSystemCompatibility::class, orphanRemoval: true)]
    private Collection $compatibilities;

    public function __construct()
    {
        $this->compatibilities = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function isHydroponic(): bool { return $this->type === 'hydroponic'; }

    public function getCompatibilities(): Collection { return $this->compatibilities; }
}
