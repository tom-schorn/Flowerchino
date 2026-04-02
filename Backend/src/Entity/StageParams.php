<?php

namespace App\Entity;

use App\Repository\StageParamsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StageParamsRepository::class)]
#[ORM\UniqueConstraint(fields: ['plant', 'stage', 'growSystem'])]
class StageParams
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stageParams')]
    #[ORM\JoinColumn(nullable: false)]
    private Plant $plant;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?GrowSystem $growSystem = null;

    #[ORM\Column(length: 20)]
    private string $stage;

    // Water & Nutrients
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phMin = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phMax = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecMin = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecMax = null;

    #[ORM\Column(nullable: true)]
    private ?int $tdsMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $tdsMax = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $waterTempMin = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $waterTempMax = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $dissolvedOxygenMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $nPpm = null;

    #[ORM\Column(nullable: true)]
    private ?int $pPpm = null;

    #[ORM\Column(nullable: true)]
    private ?int $kPpm = null;

    #[ORM\Column(nullable: true)]
    private ?int $caPpm = null;

    #[ORM\Column(nullable: true)]
    private ?int $mgPpm = null;

    #[ORM\Column(nullable: true)]
    private ?int $sPpm = null;

    // Environment
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempMin = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempMax = null;

    #[ORM\Column(nullable: true)]
    private ?int $humidityMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $humidityMax = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $vpdMin = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $vpdMax = null;

    #[ORM\Column(nullable: true)]
    private ?int $ppfdMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $ppfdMax = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $dliMin = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $dliMax = null;

    #[ORM\Column(nullable: true)]
    private ?int $photoperiodHours = null;

    // Survival thresholds (see #10)
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phSurviveMin = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phSurviveMax = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempSurviveMin = null;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempSurviveMax = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecSurviveMin = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecSurviveMax = null;

    public function getId(): ?int { return $this->id; }

    public function getPlant(): Plant { return $this->plant; }
    public function setPlant(Plant $plant): static { $this->plant = $plant; return $this; }

    public function getGrowSystem(): ?GrowSystem { return $this->growSystem; }
    public function setGrowSystem(?GrowSystem $growSystem): static { $this->growSystem = $growSystem; return $this; }

    public function getStage(): string { return $this->stage; }
    public function setStage(string $stage): static { $this->stage = $stage; return $this; }

    public function getPhMin(): ?string { return $this->phMin; }
    public function setPhMin(?string $phMin): static { $this->phMin = $phMin; return $this; }

    public function getPhMax(): ?string { return $this->phMax; }
    public function setPhMax(?string $phMax): static { $this->phMax = $phMax; return $this; }

    public function getEcMin(): ?string { return $this->ecMin; }
    public function setEcMin(?string $ecMin): static { $this->ecMin = $ecMin; return $this; }

    public function getEcMax(): ?string { return $this->ecMax; }
    public function setEcMax(?string $ecMax): static { $this->ecMax = $ecMax; return $this; }

    public function getTdsMin(): ?int { return $this->tdsMin; }
    public function setTdsMin(?int $tdsMin): static { $this->tdsMin = $tdsMin; return $this; }

    public function getTdsMax(): ?int { return $this->tdsMax; }
    public function setTdsMax(?int $tdsMax): static { $this->tdsMax = $tdsMax; return $this; }

    public function getWaterTempMin(): ?string { return $this->waterTempMin; }
    public function setWaterTempMin(?string $waterTempMin): static { $this->waterTempMin = $waterTempMin; return $this; }

    public function getWaterTempMax(): ?string { return $this->waterTempMax; }
    public function setWaterTempMax(?string $waterTempMax): static { $this->waterTempMax = $waterTempMax; return $this; }

    public function getDissolvedOxygenMin(): ?string { return $this->dissolvedOxygenMin; }
    public function setDissolvedOxygenMin(?string $dissolvedOxygenMin): static { $this->dissolvedOxygenMin = $dissolvedOxygenMin; return $this; }

    public function getNPpm(): ?int { return $this->nPpm; }
    public function setNPpm(?int $nPpm): static { $this->nPpm = $nPpm; return $this; }

    public function getPPpm(): ?int { return $this->pPpm; }
    public function setPPpm(?int $pPpm): static { $this->pPpm = $pPpm; return $this; }

    public function getKPpm(): ?int { return $this->kPpm; }
    public function setKPpm(?int $kPpm): static { $this->kPpm = $kPpm; return $this; }

    public function getCaPpm(): ?int { return $this->caPpm; }
    public function setCaPpm(?int $caPpm): static { $this->caPpm = $caPpm; return $this; }

    public function getMgPpm(): ?int { return $this->mgPpm; }
    public function setMgPpm(?int $mgPpm): static { $this->mgPpm = $mgPpm; return $this; }

    public function getSPpm(): ?int { return $this->sPpm; }
    public function setSPpm(?int $sPpm): static { $this->sPpm = $sPpm; return $this; }

    public function getAirTempMin(): ?string { return $this->airTempMin; }
    public function setAirTempMin(?string $airTempMin): static { $this->airTempMin = $airTempMin; return $this; }

    public function getAirTempMax(): ?string { return $this->airTempMax; }
    public function setAirTempMax(?string $airTempMax): static { $this->airTempMax = $airTempMax; return $this; }

    public function getHumidityMin(): ?int { return $this->humidityMin; }
    public function setHumidityMin(?int $humidityMin): static { $this->humidityMin = $humidityMin; return $this; }

    public function getHumidityMax(): ?int { return $this->humidityMax; }
    public function setHumidityMax(?int $humidityMax): static { $this->humidityMax = $humidityMax; return $this; }

    public function getVpdMin(): ?string { return $this->vpdMin; }
    public function setVpdMin(?string $vpdMin): static { $this->vpdMin = $vpdMin; return $this; }

    public function getVpdMax(): ?string { return $this->vpdMax; }
    public function setVpdMax(?string $vpdMax): static { $this->vpdMax = $vpdMax; return $this; }

    public function getPpfdMin(): ?int { return $this->ppfdMin; }
    public function setPpfdMin(?int $ppfdMin): static { $this->ppfdMin = $ppfdMin; return $this; }

    public function getPpfdMax(): ?int { return $this->ppfdMax; }
    public function setPpfdMax(?int $ppfdMax): static { $this->ppfdMax = $ppfdMax; return $this; }

    public function getDliMin(): ?string { return $this->dliMin; }
    public function setDliMin(?string $dliMin): static { $this->dliMin = $dliMin; return $this; }

    public function getDliMax(): ?string { return $this->dliMax; }
    public function setDliMax(?string $dliMax): static { $this->dliMax = $dliMax; return $this; }

    public function getPhotoperiodHours(): ?int { return $this->photoperiodHours; }
    public function setPhotoperiodHours(?int $photoperiodHours): static { $this->photoperiodHours = $photoperiodHours; return $this; }

    public function getPhSurviveMin(): ?string { return $this->phSurviveMin; }
    public function setPhSurviveMin(?string $phSurviveMin): static { $this->phSurviveMin = $phSurviveMin; return $this; }

    public function getPhSurviveMax(): ?string { return $this->phSurviveMax; }
    public function setPhSurviveMax(?string $phSurviveMax): static { $this->phSurviveMax = $phSurviveMax; return $this; }

    public function getAirTempSurviveMin(): ?string { return $this->airTempSurviveMin; }
    public function setAirTempSurviveMin(?string $airTempSurviveMin): static { $this->airTempSurviveMin = $airTempSurviveMin; return $this; }

    public function getAirTempSurviveMax(): ?string { return $this->airTempSurviveMax; }
    public function setAirTempSurviveMax(?string $airTempSurviveMax): static { $this->airTempSurviveMax = $airTempSurviveMax; return $this; }

    public function getEcSurviveMin(): ?string { return $this->ecSurviveMin; }
    public function setEcSurviveMin(?string $ecSurviveMin): static { $this->ecSurviveMin = $ecSurviveMin; return $this; }

    public function getEcSurviveMax(): ?string { return $this->ecSurviveMax; }
    public function setEcSurviveMax(?string $ecSurviveMax): static { $this->ecSurviveMax = $ecSurviveMax; return $this; }
}
