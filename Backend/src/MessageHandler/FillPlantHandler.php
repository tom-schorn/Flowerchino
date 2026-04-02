<?php

namespace App\MessageHandler;

use App\Entity\GrowSystem;
use App\Entity\GrowSystemCompatibility;
use App\Entity\StageParams;
use App\Message\FillPlantMessage;
use App\Repository\PlantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
        private LoggerInterface $logger,
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
            $this->logger->error('FillPlantHandler: Claude returned no parseable JSON', [
                'plant_id' => $message->plantId,
                'name'     => $message->canonicalName,
            ]);
            return;
        }

        $this->applyData($plant, $json);
        $this->em->flush();
        $this->logger->info('FillPlantHandler: plant filled', ['plant_id' => $message->plantId, 'grade' => $plant->getQualityGrade()]);
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
                'max_tokens' => 8192,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $body = $response->toArray(false); // false = don't throw on 4xx
        if (isset($body['error'])) {
            $this->logger->error('Anthropic API error', ['error' => $body['error']]);
            return null;
        }
        $text = $body['content'][0]['text'] ?? '';

        // Try: ```json ... ``` block
        if (preg_match('/```json\s*([\s\S]+?)\s*```/i', $text, $m)) {
            $text = $m[1];
        } elseif (preg_match('/```\s*([\s\S]+?)\s*```/i', $text, $m)) {
            // Try: ``` ... ``` block without language tag
            $text = $m[1];
        } else {
            // Try: first { ... } object in response
            if (preg_match('/(\{[\s\S]+\})/s', $text, $m)) {
                $text = $m[1];
            }
        }

        $decoded = json_decode($text, true);
        if (!$decoded) {
            $this->logger->error('FillPlantHandler: JSON parse failed', ['raw' => substr($text, 0, 500)]);
        }
        return $decoded ?: null;
    }

    private function buildPrompt(string $name, int $gbifKey): string
    {
        $stageFields = '"days_min": 5, "days_max": 14, "ph_min": 6.0, "ph_max": 6.5, "ph_warn_min": 5.8, "ph_warn_max": 6.8, "ph_crit_min": 5.0, "ph_crit_max": 7.5, "ph_tol_hours": 72, "ec_min": 1.0, "ec_max": 1.8, "ec_warn_min": 0.8, "ec_warn_max": 2.2, "ec_crit_min": 0.5, "ec_crit_max": 3.0, "ec_tol_hours": 48, "tds_min": 500, "tds_max": 900, "water_temp_min": 20, "water_temp_max": 24, "water_temp_warn_min": 18, "water_temp_warn_max": 26, "water_temp_crit_min": 14, "water_temp_crit_max": 30, "water_temp_tol_hours": 6, "air_temp_min": 22, "air_temp_max": 26, "air_temp_warn_min": 18, "air_temp_warn_max": 30, "air_temp_crit_min": 14, "air_temp_crit_max": 36, "air_temp_tol_hours": 4, "humidity_min": 60, "humidity_max": 80, "humidity_warn_min": 50, "humidity_warn_max": 90, "humidity_crit_min": 35, "humidity_crit_max": 98, "humidity_tol_hours": 20, "vpd_min": 0.6, "vpd_max": 1.0, "ppfd_min": 200, "ppfd_max": 500, "dli_min": 12, "dli_max": 20, "photoperiod_hours": 18, "n_ppm": 100, "p_ppm": 60, "k_ppm": 120, "ca_ppm": 100, "mg_ppm": 30, "s_ppm": 25';

        $soilFields = '"days_min": 5, "days_max": 14, "ph_min": 6.2, "ph_max": 7.0, "ph_warn_min": 5.8, "ph_warn_max": 7.3, "ph_crit_min": 5.0, "ph_crit_max": 8.0, "ph_tol_hours": 96, "air_temp_min": 22, "air_temp_max": 26, "air_temp_warn_min": 18, "air_temp_warn_max": 30, "air_temp_crit_min": 12, "air_temp_crit_max": 38, "air_temp_tol_hours": 6, "humidity_min": 60, "humidity_max": 80, "humidity_warn_min": 50, "humidity_warn_max": 90, "humidity_crit_min": 35, "humidity_crit_max": 98, "humidity_tol_hours": 24, "vpd_min": 0.6, "vpd_max": 1.0, "ppfd_min": 200, "ppfd_max": 500, "dli_min": 12, "dli_max": 20, "photoperiod_hours": 18, "n_ppm": 80, "p_ppm": 50, "k_ppm": 100, "ca_ppm": 80, "mg_ppm": 25, "s_ppm": 20';

        return <<<PROMPT
You are a plant cultivation expert. Provide accurate growing parameters for "{$name}" (GBIF key: {$gbifKey}).

Reply with ONLY a valid JSON object — no explanation, no text before or after, just the JSON.

Use these exact keys. Replace ALL numeric values with accurate values for this specific plant. All numbers must be JSON numbers (not strings).

For "stages" (hydroponic, use all fields including ec/tds/water_temp):
For "soil_stages" (soil growing, omit ec_*, tds_*, water_temp_* fields entirely).

Available compatible_systems values: dwc, nft, kratky, aeroponics, ebb-flow, drip, soil, coco

{
  "canonical_name": "{$name}",
  "scientific_name": "Full name with authorship",
  "authorship": "Author",
  "common_names": [{"lang": "en", "name": "English name"}, {"lang": "de", "name": "German name"}],
  "kingdom": "Plantae",
  "phylum": "Tracheophyta",
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
    "germinating": { {$stageFields} },
    "seedling":    { {$stageFields} },
    "vegetative":  { {$stageFields} },
    "flowering":   { {$stageFields} },
    "fruiting":    { {$stageFields} },
    "harvesting":  { {$stageFields} }
  },
  "soil_stages": {
    "germinating": { {$soilFields} },
    "seedling":    { {$soilFields} },
    "vegetative":  { {$soilFields} },
    "flowering":   { {$soilFields} },
    "fruiting":    { {$soilFields} },
    "harvesting":  { {$soilFields} }
  }
}
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
