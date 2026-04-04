<?php

namespace App\Entity;

use App\Repository\PlantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlantRepository::class)]
class Plant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Botanical classification
    #[ORM\Column(length: 255)]
    private string $canonicalName;

    #[ORM\Column(length: 255)]
    private string $scientificName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $authorship = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $kingdom = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $phylum = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $class = null;

    #[ORM\Column(name: 'taxon_order', length: 100, nullable: true)]
    private ?string $order = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $family = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genus = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $species = null;

    #[ORM\Column(name: 'taxon_rank', length: 50, nullable: true)]
    private ?string $rank = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taxonomicStatus = null;

    #[ORM\Column(nullable: true)]
    private ?int $acceptedNameId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ipniId = null;

    #[ORM\Column(nullable: true, unique: true)]
    private ?int $gbifKey = null;

    // Common names stored as JSON array: [{"lang": "en", "name": "Tomato"}, ...]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $commonNames = null;

    // Quality & data tracking
    #[ORM\Column(length: 20)]
    private string $qualityGrade = 'pending';

    #[ORM\Column]
    private bool $aiPrefilled = false;

    #[ORM\Column]
    private bool $communityVerified = false;

    #[ORM\Column(nullable: true)]
    private ?int $completenessScore = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastReviewedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    // Growth characteristics
    #[ORM\Column(nullable: true)]
    private ?bool $multiHarvest = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasDormant = null;

    #[ORM\Column(nullable: true)]
    private ?int $cycleDaysMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $cycleDaysMax = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $yieldPotential = null;

    #[ORM\OneToMany(mappedBy: 'plant', targetEntity: StageParams::class, orphanRemoval: true)]
    private Collection $stageParams;

    #[ORM\OneToMany(mappedBy: 'plant', targetEntity: GrowSystemCompatibility::class, orphanRemoval: true)]
    private Collection $growSystemCompatibilities;

    public function __construct()
    {
        $this->stageParams = new ArrayCollection();
        $this->growSystemCompatibilities = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getCanonicalName(): string { return $this->canonicalName; }
    public function setCanonicalName(string $canonicalName): static { $this->canonicalName = $canonicalName; return $this; }

    public function getScientificName(): string { return $this->scientificName; }
    public function setScientificName(string $scientificName): static { $this->scientificName = $scientificName; return $this; }

    public function getAuthorship(): ?string { return $this->authorship; }
    public function setAuthorship(?string $authorship): static { $this->authorship = $authorship; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getKingdom(): ?string { return $this->kingdom; }
    public function setKingdom(?string $kingdom): static { $this->kingdom = $kingdom; return $this; }

    public function getPhylum(): ?string { return $this->phylum; }
    public function setPhylum(?string $phylum): static { $this->phylum = $phylum; return $this; }

    public function getClass(): ?string { return $this->class; }
    public function setClass(?string $class): static { $this->class = $class; return $this; }

    public function getOrder(): ?string { return $this->order; }
    public function setOrder(?string $order): static { $this->order = $order; return $this; }

    public function getFamily(): ?string { return $this->family; }
    public function setFamily(?string $family): static { $this->family = $family; return $this; }

    public function getGenus(): ?string { return $this->genus; }
    public function setGenus(?string $genus): static { $this->genus = $genus; return $this; }

    public function getSpecies(): ?string { return $this->species; }
    public function setSpecies(?string $species): static { $this->species = $species; return $this; }

    public function getRank(): ?string { return $this->rank; }
    public function setRank(?string $rank): static { $this->rank = $rank; return $this; }

    public function getTaxonomicStatus(): ?string { return $this->taxonomicStatus; }
    public function setTaxonomicStatus(?string $taxonomicStatus): static { $this->taxonomicStatus = $taxonomicStatus; return $this; }

    public function getAcceptedNameId(): ?int { return $this->acceptedNameId; }
    public function setAcceptedNameId(?int $acceptedNameId): static { $this->acceptedNameId = $acceptedNameId; return $this; }

    public function getIpniId(): ?string { return $this->ipniId; }
    public function setIpniId(?string $ipniId): static { $this->ipniId = $ipniId; return $this; }

    public function getGbifKey(): ?int { return $this->gbifKey; }
    public function setGbifKey(?int $gbifKey): static { $this->gbifKey = $gbifKey; return $this; }

    public function getCommonNames(): ?array { return $this->commonNames; }
    public function setCommonNames(?array $commonNames): static { $this->commonNames = $commonNames; return $this; }

    public function getQualityGrade(): string { return $this->qualityGrade; }
    public function setQualityGrade(string $qualityGrade): static { $this->qualityGrade = $qualityGrade; return $this; }

    public function isAiPrefilled(): bool { return $this->aiPrefilled; }
    public function setAiPrefilled(bool $aiPrefilled): static { $this->aiPrefilled = $aiPrefilled; return $this; }

    public function isCommunityVerified(): bool { return $this->communityVerified; }
    public function setCommunityVerified(bool $communityVerified): static { $this->communityVerified = $communityVerified; return $this; }

    public function getCompletenessScore(): ?int { return $this->completenessScore; }
    public function setCompletenessScore(?int $completenessScore): static { $this->completenessScore = $completenessScore; return $this; }

    public function getLastReviewedAt(): ?\DateTimeInterface { return $this->lastReviewedAt; }
    public function setLastReviewedAt(?\DateTimeInterface $lastReviewedAt): static { $this->lastReviewedAt = $lastReviewedAt; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function touch(): static { $this->updatedAt = new \DateTime(); return $this; }

    public function isMultiHarvest(): ?bool { return $this->multiHarvest; }
    public function setMultiHarvest(?bool $multiHarvest): static { $this->multiHarvest = $multiHarvest; return $this; }

    public function isHasDormant(): ?bool { return $this->hasDormant; }
    public function setHasDormant(?bool $hasDormant): static { $this->hasDormant = $hasDormant; return $this; }

    public function getCycleDaysMin(): ?int { return $this->cycleDaysMin; }
    public function setCycleDaysMin(?int $cycleDaysMin): static { $this->cycleDaysMin = $cycleDaysMin; return $this; }

    public function getCycleDaysMax(): ?int { return $this->cycleDaysMax; }
    public function setCycleDaysMax(?int $cycleDaysMax): static { $this->cycleDaysMax = $cycleDaysMax; return $this; }

    public function getYieldPotential(): ?string { return $this->yieldPotential; }
    public function setYieldPotential(?string $yieldPotential): static { $this->yieldPotential = $yieldPotential; return $this; }

    public function getStageParams(): Collection { return $this->stageParams; }
    public function getGrowSystemCompatibilities(): Collection { return $this->growSystemCompatibilities; }
}
