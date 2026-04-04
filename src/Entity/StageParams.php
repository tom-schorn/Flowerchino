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

    #[ORM\Column(nullable: true)]
    private ?int $stageDaysMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $stageDaysMax = null;

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

    // Thresholds: warning (amber) + critical/lethal (red) — per #10
    // pH
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phWarnMin = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phWarnMax = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phCritMin = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private ?string $phCritMax = null;

    // EC
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecWarnMin = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecWarnMax = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecCritMin = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $ecCritMax = null;

    // Air temperature
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempWarnMin = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempWarnMax = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempCritMin = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $airTempCritMax = null;

    // Water temperature
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $waterTempWarnMin = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $waterTempWarnMax = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $waterTempCritMin = null;
    #[ORM\Column(type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $waterTempCritMax = null;

    // Humidity
    #[ORM\Column(nullable: true)]
    private ?int $humidityWarnMin = null;
    #[ORM\Column(nullable: true)]
    private ?int $humidityWarnMax = null;
    #[ORM\Column(nullable: true)]
    private ?int $humidityCritMin = null;
    #[ORM\Column(nullable: true)]
    private ?int $humidityCritMax = null;

    // Critical tolerance — hours until irreversible damage in critical zone
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $phCritToleranceHours = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $ecCritToleranceHours = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $airTempCritToleranceHours = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $waterTempCritToleranceHours = null;
    #[ORM\Column(type: 'decimal', precision: 5, scale: 1, nullable: true)]
    private ?string $humidityCritToleranceHours = null;

    public function getId(): ?int { return $this->id; }

    public function getPlant(): Plant { return $this->plant; }
    public function setPlant(Plant $plant): static { $this->plant = $plant; return $this; }

    public function getGrowSystem(): ?GrowSystem { return $this->growSystem; }
    public function setGrowSystem(?GrowSystem $growSystem): static { $this->growSystem = $growSystem; return $this; }

    public function getStage(): string { return $this->stage; }
    public function setStage(string $stage): static { $this->stage = $stage; return $this; }

    public function getStageDaysMin(): ?int { return $this->stageDaysMin; }
    public function setStageDaysMin(?int $v): static { $this->stageDaysMin = $v; return $this; }
    public function getStageDaysMax(): ?int { return $this->stageDaysMax; }
    public function setStageDaysMax(?int $v): static { $this->stageDaysMax = $v; return $this; }

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

    public function getPhWarnMin(): ?string { return $this->phWarnMin; }
    public function setPhWarnMin(?string $v): static { $this->phWarnMin = $v; return $this; }
    public function getPhWarnMax(): ?string { return $this->phWarnMax; }
    public function setPhWarnMax(?string $v): static { $this->phWarnMax = $v; return $this; }
    public function getPhCritMin(): ?string { return $this->phCritMin; }
    public function setPhCritMin(?string $v): static { $this->phCritMin = $v; return $this; }
    public function getPhCritMax(): ?string { return $this->phCritMax; }
    public function setPhCritMax(?string $v): static { $this->phCritMax = $v; return $this; }

    public function getEcWarnMin(): ?string { return $this->ecWarnMin; }
    public function setEcWarnMin(?string $v): static { $this->ecWarnMin = $v; return $this; }
    public function getEcWarnMax(): ?string { return $this->ecWarnMax; }
    public function setEcWarnMax(?string $v): static { $this->ecWarnMax = $v; return $this; }
    public function getEcCritMin(): ?string { return $this->ecCritMin; }
    public function setEcCritMin(?string $v): static { $this->ecCritMin = $v; return $this; }
    public function getEcCritMax(): ?string { return $this->ecCritMax; }
    public function setEcCritMax(?string $v): static { $this->ecCritMax = $v; return $this; }

    public function getAirTempWarnMin(): ?string { return $this->airTempWarnMin; }
    public function setAirTempWarnMin(?string $v): static { $this->airTempWarnMin = $v; return $this; }
    public function getAirTempWarnMax(): ?string { return $this->airTempWarnMax; }
    public function setAirTempWarnMax(?string $v): static { $this->airTempWarnMax = $v; return $this; }
    public function getAirTempCritMin(): ?string { return $this->airTempCritMin; }
    public function setAirTempCritMin(?string $v): static { $this->airTempCritMin = $v; return $this; }
    public function getAirTempCritMax(): ?string { return $this->airTempCritMax; }
    public function setAirTempCritMax(?string $v): static { $this->airTempCritMax = $v; return $this; }

    public function getWaterTempWarnMin(): ?string { return $this->waterTempWarnMin; }
    public function setWaterTempWarnMin(?string $v): static { $this->waterTempWarnMin = $v; return $this; }
    public function getWaterTempWarnMax(): ?string { return $this->waterTempWarnMax; }
    public function setWaterTempWarnMax(?string $v): static { $this->waterTempWarnMax = $v; return $this; }
    public function getWaterTempCritMin(): ?string { return $this->waterTempCritMin; }
    public function setWaterTempCritMin(?string $v): static { $this->waterTempCritMin = $v; return $this; }
    public function getWaterTempCritMax(): ?string { return $this->waterTempCritMax; }
    public function setWaterTempCritMax(?string $v): static { $this->waterTempCritMax = $v; return $this; }

    public function getHumidityWarnMin(): ?int { return $this->humidityWarnMin; }
    public function setHumidityWarnMin(?int $v): static { $this->humidityWarnMin = $v; return $this; }
    public function getHumidityWarnMax(): ?int { return $this->humidityWarnMax; }
    public function setHumidityWarnMax(?int $v): static { $this->humidityWarnMax = $v; return $this; }
    public function getHumidityCritMin(): ?int { return $this->humidityCritMin; }
    public function setHumidityCritMin(?int $v): static { $this->humidityCritMin = $v; return $this; }
    public function getHumidityCritMax(): ?int { return $this->humidityCritMax; }
    public function setHumidityCritMax(?int $v): static { $this->humidityCritMax = $v; return $this; }

    public function getPhCritToleranceHours(): ?string { return $this->phCritToleranceHours; }
    public function setPhCritToleranceHours(?string $v): static { $this->phCritToleranceHours = $v; return $this; }
    public function getEcCritToleranceHours(): ?string { return $this->ecCritToleranceHours; }
    public function setEcCritToleranceHours(?string $v): static { $this->ecCritToleranceHours = $v; return $this; }
    public function getAirTempCritToleranceHours(): ?string { return $this->airTempCritToleranceHours; }
    public function setAirTempCritToleranceHours(?string $v): static { $this->airTempCritToleranceHours = $v; return $this; }
    public function getWaterTempCritToleranceHours(): ?string { return $this->waterTempCritToleranceHours; }
    public function setWaterTempCritToleranceHours(?string $v): static { $this->waterTempCritToleranceHours = $v; return $this; }
    public function getHumidityCritToleranceHours(): ?string { return $this->humidityCritToleranceHours; }
    public function setHumidityCritToleranceHours(?string $v): static { $this->humidityCritToleranceHours = $v; return $this; }
}
