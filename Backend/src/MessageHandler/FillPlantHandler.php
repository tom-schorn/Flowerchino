<?php

namespace App\MessageHandler;

use App\Entity\GrowSystem;
use App\Entity\GrowSystemCompatibility;
use App\Entity\StageParams;
use App\Message\FillPlantMessage;
use App\Repository\PlantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class FillPlantHandler
{
    public function __construct(
        private PlantRepository $plants,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        #[Autowire('%env(ANTHROPIC_API_KEY)%')]
        private string $apiKey,
    ) {}

    public function __invoke(FillPlantMessage $message): void
    {
        $plant = $this->plants->find($message->plantId);
        if (!$plant || $plant->getQualityGrade() !== 'pending') {
            return;
        }

        $json = $this->askClaude($message->canonicalName, $message->gbifKey);
        if (!$json) {
            return;
        }

        $this->applyData($plant, $json);
        $this->em->flush();
    }

    private function askClaude(string $name, int $gbifKey): ?array
    {
        $prompt = $this->buildPrompt($name, $gbifKey);

        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 4096,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $body = $response->toArray();
        $text = $body['content'][0]['text'] ?? '';

        // Extract JSON block from response
        if (preg_match('/```json\s*([\s\S]+?)\s*```/', $text, $m)) {
            $text = $m[1];
        }

        return json_decode($text, true) ?: null;
    }

    private function buildPrompt(string $name, int $gbifKey): string
    {
        return <<<PROMPT
You are a plant cultivation expert. Fill in detailed growing parameters for the plant "{$name}" (GBIF key: {$gbifKey}).

Return ONLY a JSON object with this exact structure (no explanation, no markdown except the json block):

```json
{
  "canonical_name": "...",
  "scientific_name": "... Authorship",
  "authorship": "...",
  "common_names": [{"lang": "en", "name": "..."}, {"lang": "de", "name": "..."}],
  "kingdom": "Plantae",
  "phylum": "...",
  "class": "...",
  "order": "...",
  "family": "...",
  "genus": "...",
  "species": "...",
  "rank": "species",
  "taxonomic_status": "accepted",
  "cycle_days_min": 60,
  "cycle_days_max": 90,
  "multi_harvest": false,
  "has_dormant": false,
  "yield_potential": "medium",
  "compatible_systems": ["dwc", "nft", "soil"],
  "stages": {
    "germinating": {
      "days_min": 3, "days_max": 10,
      "ph_min": 5.8, "ph_max": 6.2, "ph_warn_min": 5.5, "ph_warn_max": 6.5, "ph_crit_min": 5.0, "ph_crit_max": 7.0, "ph_tol_hours": 72,
      "ec_min": 0.8, "ec_max": 1.2, "ec_warn_min": 0.5, "ec_warn_max": 1.5, "ec_crit_min": 0.3, "ec_crit_max": 2.0, "ec_tol_hours": 48,
      "tds_min": 400, "tds_max": 600,
      "water_temp_min": 22, "water_temp_max": 26, "water_temp_warn_min": 20, "water_temp_warn_max": 28, "water_temp_crit_min": 16, "water_temp_crit_max": 32, "water_temp_tol_hours": 6,
      "air_temp_min": 24, "air_temp_max": 28, "air_temp_warn_min": 20, "air_temp_warn_max": 32, "air_temp_crit_min": 16, "air_temp_crit_max": 38, "air_temp_tol_hours": 4,
      "humidity_min": 70, "humidity_max": 90, "humidity_warn_min": 60, "humidity_warn_max": 95, "humidity_crit_min": 45, "humidity_crit_max": 99, "humidity_tol_hours": 24,
      "vpd_min": 0.4, "vpd_max": 0.8,
      "ppfd_min": 100, "ppfd_max": 300, "dli_min": 6, "dli_max": 12, "photoperiod_hours": 18,
      "n_ppm": 50, "p_ppm": 40, "k_ppm": 50, "ca_ppm": 60, "mg_ppm": 20, "s_ppm": 15
    },
    "seedling": { ... same fields ... },
    "vegetative": { ... same fields ... },
    "flowering": { ... same fields ... },
    "fruiting": { ... same fields ... },
    "harvesting": { ... same fields ... }
  },
  "soil_stages": {
    "germinating": {
      "days_min": 5, "days_max": 14,
      "ph_min": 6.0, "ph_max": 7.0, "ph_warn_min": 5.5, "ph_warn_max": 7.5, "ph_crit_min": 5.0, "ph_crit_max": 8.0, "ph_tol_hours": 96,
      "air_temp_min": 22, "air_temp_max": 28, "air_temp_warn_min": 18, "air_temp_warn_max": 33, "air_temp_crit_min": 12, "air_temp_crit_max": 40, "air_temp_tol_hours": 6,
      "humidity_min": 65, "humidity_max": 85, "humidity_warn_min": 55, "humidity_warn_max": 92, "humidity_crit_min": 40, "humidity_crit_max": 98, "humidity_tol_hours": 30,
      "vpd_min": 0.4, "vpd_max": 0.8,
      "ppfd_min": 100, "ppfd_max": 300, "dli_min": 6, "dli_max": 12, "photoperiod_hours": 18,
      "n_ppm": 30, "p_ppm": 25, "k_ppm": 30, "ca_ppm": 40, "mg_ppm": 15, "s_ppm": 10
    },
    "seedling": { ... same fields, no ec/tds/water_temp ... },
    "vegetative": { ... },
    "flowering": { ... },
    "fruiting": { ... },
    "harvesting": { ... }
  }
}
```

Use accurate, stage-differentiated values based on published horticultural data. All decimal fields as numbers, not strings. Omit ec/tds/water_temp from soil_stages entirely (set them null).
PROMPT;
    }

    private function applyData(\App\Entity\Plant $plant, array $data): void
    {
        if (!empty($data['scientific_name'])) $plant->setScientificName($data['scientific_name']);
        if (!empty($data['authorship']))      $plant->setAuthorship($data['authorship']);
        if (!empty($data['common_names']))    $plant->setCommonNames($data['common_names']);
        if (!empty($data['kingdom']))         $plant->setKingdom($data['kingdom']);
        if (!empty($data['phylum']))          $plant->setPhylum($data['phylum']);
        if (!empty($data['class']))           $plant->setClass($data['class']);
        if (!empty($data['order']))           $plant->setOrder($data['order']);
        if (!empty($data['family']))          $plant->setFamily($data['family']);
        if (!empty($data['genus']))           $plant->setGenus($data['genus']);
        if (!empty($data['species']))         $plant->setSpecies($data['species']);
        if (!empty($data['rank']))            $plant->setRank($data['rank']);
        if (!empty($data['taxonomic_status'])) $plant->setTaxonomicStatus($data['taxonomic_status']);
        if (isset($data['cycle_days_min']))   $plant->setCycleDaysMin((int)$data['cycle_days_min']);
        if (isset($data['cycle_days_max']))   $plant->setCycleDaysMax((int)$data['cycle_days_max']);
        if (isset($data['multi_harvest']))    $plant->setMultiHarvest((bool)$data['multi_harvest']);
        if (isset($data['has_dormant']))      $plant->setHasDormant((bool)$data['has_dormant']);
        if (!empty($data['yield_potential'])) $plant->setYieldPotential($data['yield_potential']);

        // Grow system compatibilities
        if (!empty($data['compatible_systems'])) {
            $this->applyCompatibilities($plant, $data['compatible_systems']);
        }

        // Stage params — hydroponic (dwc)
        if (!empty($data['stages'])) {
            $dwc = $this->getOrCreateGrowSystem('dwc', 'Deep Water Culture', 'hydroponic');
            foreach ($data['stages'] as $stage => $p) {
                $sp = $this->findOrCreateStageParams($plant, $stage, $dwc);
                $this->fillStageParams($sp, $p, hydro: true);
            }
        }

        // Stage params — soil
        if (!empty($data['soil_stages'])) {
            $soil = $this->getOrCreateGrowSystem('soil', 'Soil', 'soil');
            foreach ($data['soil_stages'] as $stage => $p) {
                $sp = $this->findOrCreateStageParams($plant, $stage, $soil);
                $this->fillStageParams($sp, $p, hydro: false);
            }
        }

        $plant->setQualityGrade('draft');
        $plant->setAiPrefilled(true);
        $plant->setCompletenessScore($this->calcCompleteness($plant));
    }

    private function applyCompatibilities(\App\Entity\Plant $plant, array $slugs): void
    {
        // Remove existing
        foreach ($plant->getGrowSystemCompatibilities() as $c) {
            $this->em->remove($c);
        }

        $systemDefs = [
            'dwc'        => ['Deep Water Culture', 'hydroponic'],
            'nft'        => ['Nutrient Film Technique', 'hydroponic'],
            'kratky'     => ['Kratky', 'hydroponic'],
            'aeroponics' => ['Aeroponics', 'hydroponic'],
            'ebb-flow'   => ['Ebb & Flow', 'hydroponic'],
            'drip'       => ['Drip', 'hydroponic'],
            'soil'       => ['Soil', 'soil'],
            'coco'       => ['Coco Coir', 'coco'],
        ];

        foreach ($slugs as $slug) {
            if (!isset($systemDefs[$slug])) continue;
            [$name, $type] = $systemDefs[$slug];
            $gs    = $this->getOrCreateGrowSystem($slug, $name, $type);
            $compat = new GrowSystemCompatibility();
            $compat->setPlant($plant)->setGrowSystem($gs);
            $this->em->persist($compat);
        }
    }

    private function fillStageParams(StageParams $sp, array $p, bool $hydro): void
    {
        if (isset($p['days_min']))      $sp->setStageDaysMin((int)$p['days_min']);
        if (isset($p['days_max']))      $sp->setStageDaysMax((int)$p['days_max']);

        // pH
        if (isset($p['ph_min']))          $sp->setPhMin((string)$p['ph_min'])->setPhMax((string)$p['ph_max']);
        if (isset($p['ph_warn_min']))     $sp->setPhWarnMin((string)$p['ph_warn_min'])->setPhWarnMax((string)$p['ph_warn_max']);
        if (isset($p['ph_crit_min']))     $sp->setPhCritMin((string)$p['ph_crit_min'])->setPhCritMax((string)$p['ph_crit_max']);
        if (isset($p['ph_tol_hours']))    $sp->setPhCritToleranceHours((string)$p['ph_tol_hours']);

        // EC (hydro only)
        if ($hydro && isset($p['ec_min'])) {
            $sp->setEcMin((string)$p['ec_min'])->setEcMax((string)$p['ec_max']);
            if (isset($p['ec_warn_min'])) $sp->setEcWarnMin((string)$p['ec_warn_min'])->setEcWarnMax((string)$p['ec_warn_max']);
            if (isset($p['ec_crit_min'])) $sp->setEcCritMin((string)$p['ec_crit_min'])->setEcCritMax((string)$p['ec_crit_max']);
            if (isset($p['ec_tol_hours'])) $sp->setEcCritToleranceHours((string)$p['ec_tol_hours']);
        }
        if ($hydro && isset($p['tds_min'])) $sp->setTdsMin((int)$p['tds_min'])->setTdsMax((int)$p['tds_max']);
        if ($hydro && isset($p['water_temp_min'])) {
            $sp->setWaterTempMin((string)$p['water_temp_min'])->setWaterTempMax((string)$p['water_temp_max']);
            if (isset($p['water_temp_warn_min'])) $sp->setWaterTempWarnMin((string)$p['water_temp_warn_min'])->setWaterTempWarnMax((string)$p['water_temp_warn_max']);
            if (isset($p['water_temp_crit_min'])) $sp->setWaterTempCritMin((string)$p['water_temp_crit_min'])->setWaterTempCritMax((string)$p['water_temp_crit_max']);
            if (isset($p['water_temp_tol_hours'])) $sp->setWaterTempCritToleranceHours((string)$p['water_temp_tol_hours']);
        }

        // Air temp
        if (isset($p['air_temp_min']))        $sp->setAirTempMin((string)$p['air_temp_min'])->setAirTempMax((string)$p['air_temp_max']);
        if (isset($p['air_temp_warn_min']))   $sp->setAirTempWarnMin((string)$p['air_temp_warn_min'])->setAirTempWarnMax((string)$p['air_temp_warn_max']);
        if (isset($p['air_temp_crit_min']))   $sp->setAirTempCritMin((string)$p['air_temp_crit_min'])->setAirTempCritMax((string)$p['air_temp_crit_max']);
        if (isset($p['air_temp_tol_hours']))  $sp->setAirTempCritToleranceHours((string)$p['air_temp_tol_hours']);

        // Humidity
        if (isset($p['humidity_min']))        $sp->setHumidityMin((int)$p['humidity_min'])->setHumidityMax((int)$p['humidity_max']);
        if (isset($p['humidity_warn_min']))   $sp->setHumidityWarnMin((int)$p['humidity_warn_min'])->setHumidityWarnMax((int)$p['humidity_warn_max']);
        if (isset($p['humidity_crit_min']))   $sp->setHumidityCritMin((int)$p['humidity_crit_min'])->setHumidityCritMax((int)$p['humidity_crit_max']);
        if (isset($p['humidity_tol_hours'])) $sp->setHumidityCritToleranceHours((string)$p['humidity_tol_hours']);

        // Light & VPD
        if (isset($p['vpd_min']))         $sp->setVpdMin((string)$p['vpd_min'])->setVpdMax((string)$p['vpd_max']);
        if (isset($p['ppfd_min']))        $sp->setPpfdMin((int)$p['ppfd_min'])->setPpfdMax((int)$p['ppfd_max']);
        if (isset($p['dli_min']))         $sp->setDliMin((string)$p['dli_min'])->setDliMax((string)$p['dli_max']);
        if (isset($p['photoperiod_hours'])) $sp->setPhotoperiodHours((int)$p['photoperiod_hours']);

        // Nutrients
        if (isset($p['n_ppm']))  $sp->setNPpm((int)$p['n_ppm']);
        if (isset($p['p_ppm']))  $sp->setPPpm((int)$p['p_ppm']);
        if (isset($p['k_ppm']))  $sp->setKPpm((int)$p['k_ppm']);
        if (isset($p['ca_ppm'])) $sp->setCaPpm((int)$p['ca_ppm']);
        if (isset($p['mg_ppm'])) $sp->setMgPpm((int)$p['mg_ppm']);
        if (isset($p['s_ppm']))  $sp->setSPpm((int)$p['s_ppm']);
    }

    private function getOrCreateGrowSystem(string $slug, string $name, string $type): GrowSystem
    {
        $gs = $this->em->getRepository(GrowSystem::class)->findOneBy(['slug' => $slug]);
        if (!$gs) {
            $gs = new GrowSystem();
            $gs->setSlug($slug)->setName($name)->setType($type);
            $this->em->persist($gs);
        }
        return $gs;
    }

    private function findOrCreateStageParams(\App\Entity\Plant $plant, string $stage, GrowSystem $gs): StageParams
    {
        foreach ($plant->getStageParams() as $sp) {
            if ($sp->getStage() === $stage && $sp->getGrowSystem()?->getSlug() === $gs->getSlug()) {
                return $sp;
            }
        }
        $sp = new StageParams();
        $sp->setPlant($plant)->setStage($stage)->setGrowSystem($gs);
        $this->em->persist($sp);
        return $sp;
    }

    private function calcCompleteness(\App\Entity\Plant $plant): int
    {
        $filled = 0;
        $total  = 8;
        if ($plant->getCanonicalName())     $filled++;
        if ($plant->getFamily())            $filled++;
        if ($plant->getGenus())             $filled++;
        if ($plant->getCommonNames())       $filled++;
        if ($plant->getCycleDaysMin())      $filled++;
        if ($plant->getGbifKey())           $filled++;
        if ($plant->getStageParams()->count() >= 6) $filled++;
        if ($plant->getGrowSystemCompatibilities()->count() > 0) $filled++;
        return (int) round($filled / $total * 100);
    }
}
