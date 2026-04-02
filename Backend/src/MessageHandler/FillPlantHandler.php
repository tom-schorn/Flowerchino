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
    private const STAGES = ['germinating', 'seedling', 'vegetative', 'flowering', 'fruiting', 'harvesting'];

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

        $name    = $message->canonicalName;
        $gbifKey = $message->gbifKey;

        // ── Call 1: Botanik + Metadaten ──────────────────────────────────
        $meta = $this->claude($this->promptMeta($name, $gbifKey));
        if (!$meta) {
            $this->logger->error('FillPlantHandler: meta call failed', ['plant_id' => $message->plantId]);
            return;
        }
        $this->applyMeta($plant, $meta);
        $this->em->flush();

        // ── Calls 2–7: Hydro (1 Stage pro Call) ─────────────────────────
        $dwc = $this->getOrCreateGrowSystem('dwc', 'Deep Water Culture', 'hydroponic');
        foreach (self::STAGES as $stage) {
            $data = $this->claude($this->promptSingleStage($name, $stage, 'hydroponic'));
            if (!$data) continue;
            $sp = $this->findOrCreateStageParams($plant, $stage, $dwc);
            $this->fillStageParams($sp, $data, hydro: true);
        }
        $this->em->flush();

        // ── Calls 8–13: Soil (1 Stage pro Call) ─────────────────────────
        $soil = $this->getOrCreateGrowSystem('soil', 'Soil', 'soil');
        foreach (self::STAGES as $stage) {
            $data = $this->claude($this->promptSingleStage($name, $stage, 'soil'));
            if (!$data) continue;
            $sp = $this->findOrCreateStageParams($plant, $stage, $soil);
            $this->fillStageParams($sp, $data, hydro: false);
        }
        $this->em->flush();

        $plant->setQualityGrade('draft');
        $plant->setAiPrefilled(true);
        $plant->setCompletenessScore($this->calcCompleteness($plant));
        $this->em->flush();

        $this->logger->info('FillPlantHandler: plant filled', [
            'plant_id' => $message->plantId,
            'grade'    => $plant->getQualityGrade(),
        ]);
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

        $text = $body['content'][0]['text'] ?? '';
        return $this->parseJson($text);
    }

    private function parseJson(string $text): ?array
    {
        if (preg_match('/```json\s*([\s\S]+?)\s*```/i', $text, $m)) {
            $text = $m[1];
        } elseif (preg_match('/```\s*([\s\S]+?)\s*```/i', $text, $m)) {
            $text = $m[1];
        } elseif (preg_match('/(\{[\s\S]+\})/s', $text, $m)) {
            $text = $m[1];
        }

        $decoded = json_decode($text, true);
        if (!$decoded) {
            $this->logger->error('FillPlantHandler: JSON parse failed', ['raw' => substr($text, 0, 300)]);
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

    private function applyMeta(\App\Entity\Plant $plant, array $data): void
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

        if (!empty($data['compatible_systems'])) {
            $this->applyCompatibilities($plant, $data['compatible_systems']);
        }
    }

    private function applyCompatibilities(\App\Entity\Plant $plant, array $slugs): void
    {
        foreach ($plant->getGrowSystemCompatibilities() as $c) {
            $this->em->remove($c);
        }
        $this->em->flush(); // DELETE erst ausführen bevor neue INSERTs

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
            if (isset($p['ec_min']))           $sp->setEcMin((string)$p['ec_min'])->setEcMax((string)$p['ec_max']);
            if (isset($p['ec_warn_min']))      $sp->setEcWarnMin((string)$p['ec_warn_min'])->setEcWarnMax((string)$p['ec_warn_max']);
            if (isset($p['ec_crit_min']))      $sp->setEcCritMin((string)$p['ec_crit_min'])->setEcCritMax((string)$p['ec_crit_max']);
            if (isset($p['ec_tol_hours']))     $sp->setEcCritToleranceHours((string)$p['ec_tol_hours']);
            if (isset($p['tds_min']))          $sp->setTdsMin((int)$p['tds_min'])->setTdsMax((int)$p['tds_max']);
            if (isset($p['water_temp_min']))   $sp->setWaterTempMin((string)$p['water_temp_min'])->setWaterTempMax((string)$p['water_temp_max']);
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

        if (isset($p['vpd_min']))          $sp->setVpdMin((string)$p['vpd_min'])->setVpdMax((string)$p['vpd_max']);
        if (isset($p['ppfd_min']))         $sp->setPpfdMin((int)$p['ppfd_min'])->setPpfdMax((int)$p['ppfd_max']);
        if (isset($p['dli_min']))          $sp->setDliMin((string)$p['dli_min'])->setDliMax((string)$p['dli_max']);
        if (isset($p['photoperiod_hours'])) $sp->setPhotoperiodHours((int)$p['photoperiod_hours']);

        if (isset($p['n_ppm']))  $sp->setNPpm((int)$p['n_ppm']);
        if (isset($p['p_ppm']))  $sp->setPPpm((int)$p['p_ppm']);
        if (isset($p['k_ppm']))  $sp->setKPpm((int)$p['k_ppm']);
        if (isset($p['ca_ppm'])) $sp->setCaPpm((int)$p['ca_ppm']);
        if (isset($p['mg_ppm'])) $sp->setMgPpm((int)$p['mg_ppm']);
        if (isset($p['s_ppm']))  $sp->setSPpm((int)$p['s_ppm']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

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
        if ($plant->getCanonicalName())                              $filled++;
        if ($plant->getFamily())                                     $filled++;
        if ($plant->getGenus())                                      $filled++;
        if ($plant->getCommonNames())                                $filled++;
        if ($plant->getCycleDaysMin())                               $filled++;
        if ($plant->getGbifKey())                                    $filled++;
        if ($plant->getStageParams()->count() >= 6)                  $filled++;
        if ($plant->getGrowSystemCompatibilities()->count() > 0)     $filled++;
        return (int) round($filled / $total * 100);
    }
}
