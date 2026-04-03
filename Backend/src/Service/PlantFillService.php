<?php

namespace App\Service;

use App\Entity\GrowSystem;
use App\Entity\GrowSystemCompatibility;
use App\Entity\Plant;
use App\Entity\StageParams;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PlantFillService
{
    private const STAGES = ['germinating', 'seedling', 'vegetative', 'flowering', 'fruiting', 'harvesting'];

    private const STAGE_LABELS = [
        'germinating' => 'Germinating',
        'seedling'    => 'Seedling',
        'vegetative'  => 'Vegetative',
        'flowering'   => 'Flowering',
        'fruiting'    => 'Fruiting',
        'harvesting'  => 'Harvesting',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire('%env(ANTHROPIC_API_KEY)%')]
        private string $apiKey,
    ) {}

    /**
     * Fill a plant with AI-generated data.
     * $progress is called after each step: $progress(string $step, string $label, int $current, int $total)
     */
    public function fill(Plant $plant, ?callable $progress = null): bool
    {
        $name    = $plant->getCanonicalName();
        $gbifKey = (int) $plant->getGbifKey();
        $total   = 1 + count(self::STAGES) * 2; // 1 meta + 6 hydro + 6 soil = 13
        $current = 0;

        // ── Step 1: Botanik & Metadaten ──────────────────────────────────
        $current++;
        $progress && $progress('meta', 'Botanical data & taxonomy', $current, $total);

        $meta = $this->claude($this->promptMeta($name, $gbifKey));
        if (!$meta) {
            $this->logger->error('PlantFillService: meta call failed', ['plant_id' => $plant->getId()]);
            return false;
        }
        $this->applyMeta($plant, $meta);
        $this->em->flush();

        // ── Steps 2–7: Hydroponic stages ────────────────────────────────
        $dwc = $this->getOrCreateGrowSystem('dwc', 'Deep Water Culture', 'hydroponic');
        foreach (self::STAGES as $stage) {
            $current++;
            $progress && $progress(
                'hydro_' . $stage,
                'Hydroponic · ' . self::STAGE_LABELS[$stage],
                $current,
                $total
            );

            $data = $this->claude($this->promptSingleStage($name, $stage, 'hydroponic'));
            if ($data) {
                $sp = $this->findOrCreateStageParams($plant, $stage, $dwc);
                $this->fillStageParams($sp, $data, hydro: true);
            }
        }
        $this->em->flush();

        // ── Steps 8–13: Soil stages ──────────────────────────────────────
        $soil = $this->getOrCreateGrowSystem('soil', 'Soil', 'soil');
        foreach (self::STAGES as $stage) {
            $current++;
            $progress && $progress(
                'soil_' . $stage,
                'Soil · ' . self::STAGE_LABELS[$stage],
                $current,
                $total
            );

            $data = $this->claude($this->promptSingleStage($name, $stage, 'soil'));
            if ($data) {
                $sp = $this->findOrCreateStageParams($plant, $stage, $soil);
                $this->fillStageParams($sp, $data, hydro: false);
            }
        }
        $this->em->flush();

        $plant->setQualityGrade('draft');
        $plant->setAiPrefilled(true);
        $plant->setCompletenessScore($this->calcCompleteness($plant));
        $plant->touch();
        $this->em->flush();

        return true;
    }

    // ── Claude API ────────────────────────────────────────────────────────

    private function claude(string $prompt): ?array
    {
        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 2048,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ],
        ]);

        $body = $response->toArray(false);
        if (isset($body['error'])) {
            $this->logger->error('Anthropic API error', ['error' => $body['error']]);
            return null;
        }

        return $this->parseJson($body['content'][0]['text'] ?? '');
    }

    private function parseJson(string $text): ?array
    {
        if (preg_match('/```json\s*([\s\S]+?)\s*```/i', $text, $m))      $text = $m[1];
        elseif (preg_match('/```\s*([\s\S]+?)\s*```/i', $text, $m))      $text = $m[1];
        elseif (preg_match('/(\{[\s\S]+\})/s', $text, $m))               $text = $m[1];

        $decoded = json_decode($text, true);
        if (!$decoded) {
            $this->logger->error('PlantFillService: JSON parse failed', ['raw' => substr($text, 0, 300)]);
        }
        return $decoded ?: null;
    }

    // ── Prompts ───────────────────────────────────────────────────────────

    private function promptMeta(string $name, int $gbifKey): string
    {
        return <<<PROMPT
You are a botanist. Return ONLY a JSON object — no explanation, no markdown wrapper.

Fill in accurate data for the plant "{$name}" (GBIF key: {$gbifKey}).

{
  "scientific_name": "Full name with authorship",
  "authorship": "L.",
  "common_names": [{"lang": "en", "name": "..."}, {"lang": "de", "name": "..."}, {"lang": "es", "name": "..."}, {"lang": "fr", "name": "..."}],
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
  "compatible_systems": ["dwc", "nft", "soil"]
}

compatible_systems must only contain values from: dwc, nft, kratky, aeroponics, ebb-flow, drip, soil, coco
yield_potential must be: low, medium, or high
All numbers as JSON numbers, not strings.
PROMPT;
    }

    private function promptSingleStage(string $name, string $stage, string $medium): string
    {
        $isHydro     = $medium === 'hydroponic';
        $mediumLabel = $isHydro ? 'hydroponic (DWC)' : 'soil';
        $ecFields    = $isHydro
            ? '"ec_min": 1.2, "ec_max": 1.8, "ec_warn_min": 0.8, "ec_warn_max": 2.2, "ec_crit_min": 0.4, "ec_crit_max": 3.0, "ec_tol_hours": 48, "tds_min": 600, "tds_max": 900, "water_temp_min": 20, "water_temp_max": 24, "water_temp_warn_min": 18, "water_temp_warn_max": 26, "water_temp_crit_min": 14, "water_temp_crit_max": 30, "water_temp_tol_hours": 6,'
            : '';

        return <<<PROMPT
You are a plant cultivation expert. Return ONLY a flat JSON object — no explanation, no markdown.

Provide accurate {$mediumLabel} growing parameters for the "{$stage}" stage of "{$name}".

Return exactly this structure with all values replaced by accurate numbers for this plant and stage:

{
  "days_min": 5, "days_max": 14,
  "ph_min": 6.0, "ph_max": 6.5,
  "ph_warn_min": 5.8, "ph_warn_max": 6.8,
  "ph_crit_min": 5.0, "ph_crit_max": 7.5,
  "ph_tol_hours": 72,
  {$ecFields}
  "air_temp_min": 22, "air_temp_max": 28,
  "air_temp_warn_min": 18, "air_temp_warn_max": 32,
  "air_temp_crit_min": 12, "air_temp_crit_max": 40,
  "air_temp_tol_hours": 4,
  "humidity_min": 60, "humidity_max": 80,
  "humidity_warn_min": 50, "humidity_warn_max": 90,
  "humidity_crit_min": 35, "humidity_crit_max": 98,
  "humidity_tol_hours": 20,
  "vpd_min": 0.6, "vpd_max": 1.0,
  "ppfd_min": 200, "ppfd_max": 600,
  "dli_min": 12, "dli_max": 22,
  "photoperiod_hours": 18,
  "n_ppm": 120, "p_ppm": 60, "k_ppm": 150, "ca_ppm": 120, "mg_ppm": 40, "s_ppm": 30
}
PROMPT;
    }

    // ── Data application ──────────────────────────────────────────────────

    private function applyMeta(Plant $plant, array $data): void
    {
        if (!empty($data['scientific_name']))  $plant->setScientificName($data['scientific_name']);
        if (!empty($data['authorship']))       $plant->setAuthorship($data['authorship']);
        if (!empty($data['common_names']))     $plant->setCommonNames($data['common_names']);
        if (!empty($data['kingdom']))          $plant->setKingdom($data['kingdom']);
        if (!empty($data['phylum']))           $plant->setPhylum($data['phylum']);
        if (!empty($data['class']))            $plant->setClass($data['class']);
        if (!empty($data['order']))            $plant->setOrder($data['order']);
        if (!empty($data['family']))           $plant->setFamily($data['family']);
        if (!empty($data['genus']))            $plant->setGenus($data['genus']);
        if (!empty($data['species']))          $plant->setSpecies($data['species']);
        if (!empty($data['rank']))             $plant->setRank($data['rank']);
        if (!empty($data['taxonomic_status'])) $plant->setTaxonomicStatus($data['taxonomic_status']);
        if (isset($data['cycle_days_min']))    $plant->setCycleDaysMin((int)$data['cycle_days_min']);
        if (isset($data['cycle_days_max']))    $plant->setCycleDaysMax((int)$data['cycle_days_max']);
        if (isset($data['multi_harvest']))     $plant->setMultiHarvest((bool)$data['multi_harvest']);
        if (isset($data['has_dormant']))       $plant->setHasDormant((bool)$data['has_dormant']);
        if (!empty($data['yield_potential']))  $plant->setYieldPotential($data['yield_potential']);
        if (!empty($data['compatible_systems'])) $this->applyCompatibilities($plant, $data['compatible_systems']);
    }

    private function applyCompatibilities(Plant $plant, array $slugs): void
    {
        foreach ($plant->getGrowSystemCompatibilities() as $c) {
            $this->em->remove($c);
        }
        $this->em->flush();

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
            $gs     = $this->getOrCreateGrowSystem($slug, $name, $type);
            $compat = new GrowSystemCompatibility();
            $compat->setPlant($plant)->setGrowSystem($gs);
            $this->em->persist($compat);
        }
    }

    private function fillStageParams(StageParams $sp, array $p, bool $hydro): void
    {
        if (isset($p['days_min'])) $sp->setStageDaysMin((int)$p['days_min'])->setStageDaysMax((int)$p['days_max']);

        if (isset($p['ph_min']))       $sp->setPhMin((string)$p['ph_min'])->setPhMax((string)$p['ph_max']);
        if (isset($p['ph_warn_min']))  $sp->setPhWarnMin((string)$p['ph_warn_min'])->setPhWarnMax((string)$p['ph_warn_max']);
        if (isset($p['ph_crit_min']))  $sp->setPhCritMin((string)$p['ph_crit_min'])->setPhCritMax((string)$p['ph_crit_max']);
        if (isset($p['ph_tol_hours'])) $sp->setPhCritToleranceHours((string)$p['ph_tol_hours']);

        if ($hydro) {
            if (isset($p['ec_min']))              $sp->setEcMin((string)$p['ec_min'])->setEcMax((string)$p['ec_max']);
            if (isset($p['ec_warn_min']))         $sp->setEcWarnMin((string)$p['ec_warn_min'])->setEcWarnMax((string)$p['ec_warn_max']);
            if (isset($p['ec_crit_min']))         $sp->setEcCritMin((string)$p['ec_crit_min'])->setEcCritMax((string)$p['ec_crit_max']);
            if (isset($p['ec_tol_hours']))        $sp->setEcCritToleranceHours((string)$p['ec_tol_hours']);
            if (isset($p['tds_min']))             $sp->setTdsMin((int)$p['tds_min'])->setTdsMax((int)$p['tds_max']);
            if (isset($p['water_temp_min']))      $sp->setWaterTempMin((string)$p['water_temp_min'])->setWaterTempMax((string)$p['water_temp_max']);
            if (isset($p['water_temp_warn_min'])) $sp->setWaterTempWarnMin((string)$p['water_temp_warn_min'])->setWaterTempWarnMax((string)$p['water_temp_warn_max']);
            if (isset($p['water_temp_crit_min'])) $sp->setWaterTempCritMin((string)$p['water_temp_crit_min'])->setWaterTempCritMax((string)$p['water_temp_crit_max']);
            if (isset($p['water_temp_tol_hours'])) $sp->setWaterTempCritToleranceHours((string)$p['water_temp_tol_hours']);
        }

        if (isset($p['air_temp_min']))       $sp->setAirTempMin((string)$p['air_temp_min'])->setAirTempMax((string)$p['air_temp_max']);
        if (isset($p['air_temp_warn_min']))  $sp->setAirTempWarnMin((string)$p['air_temp_warn_min'])->setAirTempWarnMax((string)$p['air_temp_warn_max']);
        if (isset($p['air_temp_crit_min']))  $sp->setAirTempCritMin((string)$p['air_temp_crit_min'])->setAirTempCritMax((string)$p['air_temp_crit_max']);
        if (isset($p['air_temp_tol_hours'])) $sp->setAirTempCritToleranceHours((string)$p['air_temp_tol_hours']);

        if (isset($p['humidity_min']))       $sp->setHumidityMin((int)$p['humidity_min'])->setHumidityMax((int)$p['humidity_max']);
        if (isset($p['humidity_warn_min']))  $sp->setHumidityWarnMin((int)$p['humidity_warn_min'])->setHumidityWarnMax((int)$p['humidity_warn_max']);
        if (isset($p['humidity_crit_min']))  $sp->setHumidityCritMin((int)$p['humidity_crit_min'])->setHumidityCritMax((int)$p['humidity_crit_max']);
        if (isset($p['humidity_tol_hours'])) $sp->setHumidityCritToleranceHours((string)$p['humidity_tol_hours']);

        if (isset($p['vpd_min']))           $sp->setVpdMin((string)$p['vpd_min'])->setVpdMax((string)$p['vpd_max']);
        if (isset($p['ppfd_min']))          $sp->setPpfdMin((int)$p['ppfd_min'])->setPpfdMax((int)$p['ppfd_max']);
        if (isset($p['dli_min']))           $sp->setDliMin((string)$p['dli_min'])->setDliMax((string)$p['dli_max']);
        if (isset($p['photoperiod_hours'])) $sp->setPhotoperiodHours((int)$p['photoperiod_hours']);

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

    private function findOrCreateStageParams(Plant $plant, string $stage, GrowSystem $gs): StageParams
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

    private function calcCompleteness(Plant $plant): int
    {
        $score = 0;

        // ── Botanik (25 Punkte) ──────────────────────────────────────────
        if ($plant->getCanonicalName())      $score += 5;
        if ($plant->getScientificName())     $score += 2;
        if ($plant->getAuthorship())         $score += 1;
        if ($plant->getFamily())             $score += 3;
        if ($plant->getGenus())              $score += 3;
        if ($plant->getOrder())              $score += 1;
        if ($plant->getPhylum())             $score += 1;
        if ($plant->getTaxonomicStatus())    $score += 1;
        if ($plant->getGbifKey())            $score += 3;
        if ($plant->getIpniId())             $score += 1;
        if ($plant->getCommonNames() && count($plant->getCommonNames()) >= 2) $score += 4;

        // ── Wachstum (15 Punkte) ─────────────────────────────────────────
        if ($plant->getCycleDaysMin())       $score += 4;
        if ($plant->getYieldPotential())     $score += 3;
        if ($plant->isMultiHarvest() !== null) $score += 2;
        if ($plant->isHasDormant() !== null) $score += 2;
        if ($plant->getGrowSystemCompatibilities()->count() > 0) $score += 4;

        // ── Stage Params (60 Punkte = 5 pro Stage × 6 Stages × 2 Medien) ──
        // Hydro: 6 Stages × 5 Punkte = 30
        // Soil:  6 Stages × 5 Punkte = 30
        $stagesBySystem = [];
        foreach ($plant->getStageParams() as $sp) {
            $type = $sp->getGrowSystem()?->getType() ?? 'hydroponic';
            $stagesBySystem[$type][$sp->getStage()] = $sp;
        }

        foreach (['hydroponic', 'soil'] as $medium) {
            foreach (self::STAGES as $stage) {
                $sp = $stagesBySystem[$medium][$stage] ?? null;
                if (!$sp) continue;

                $pts = 0;
                if ($sp->getPhMin())      $pts++;
                if ($sp->getAirTempMin()) $pts++;
                if ($sp->getHumidityMin()) $pts++;
                if ($sp->getPpfdMin())    $pts++;
                // Hydro-spezifische Felder zählen extra
                if ($medium === 'hydroponic') {
                    if ($sp->getEcMin()) $pts++;
                } else {
                    if ($sp->getNPpm())  $pts++;
                }
                $score += $pts;
            }
        }

        return min(100, $score);
    }
}
