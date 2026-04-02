<?php

namespace App\Command;

use App\Entity\GrowSystem;
use App\Entity\GrowSystemCompatibility;
use App\Entity\Plant;
use App\Entity\StageParams;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed:test', description: 'Seed test data')]
class SeedTestDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // GrowSystems
        $hydro = [
            ['name' => 'Deep Water Culture', 'slug' => 'dwc', 'type' => 'hydroponic'],
            ['name' => 'Nutrient Film Technique', 'slug' => 'nft', 'type' => 'hydroponic'],
            ['name' => 'Kratky', 'slug' => 'kratky', 'type' => 'hydroponic'],
            ['name' => 'Aeroponics', 'slug' => 'aeroponics', 'type' => 'hydroponic'],
            ['name' => 'Ebb & Flow', 'slug' => 'ebb-flow', 'type' => 'hydroponic'],
            ['name' => 'Drip', 'slug' => 'drip', 'type' => 'hydroponic'],
        ];
        $nonHydro = [
            ['name' => 'Soil', 'slug' => 'soil', 'type' => 'soil'],
            ['name' => 'Coco Coir', 'slug' => 'coco', 'type' => 'coco'],
        ];

        $gs = [];
        foreach (array_merge($hydro, $nonHydro) as $s) {
            $entity = new GrowSystem();
            $entity->setName($s['name'])->setSlug($s['slug'])->setType($s['type']);
            $this->em->persist($entity);
            $gs[$s['slug']] = $entity;
        }

        // Plant: Citrullus lanatus
        $plant = new Plant();
        $plant->setCanonicalName('Citrullus lanatus');
        $plant->setScientificName('Citrullus lanatus (Thunb.) Matsum. & Nakai');
        $plant->setAuthorship('(Thunb.) Matsum. & Nakai');
        $plant->setSlug('citrullus-lanatus');
        $plant->setKingdom('Plantae');
        $plant->setPhylum('Tracheophyta');
        $plant->setClass('Magnoliopsida');
        $plant->setOrder('Cucurbitales');
        $plant->setFamily('Cucurbitaceae');
        $plant->setGenus('Citrullus');
        $plant->setSpecies('lanatus');
        $plant->setRank('species');
        $plant->setTaxonomicStatus('accepted');
        $plant->setGbifKey(5297380);
        $plant->setIpniId('867323-1');
        $plant->setCommonNames([
            ['lang' => 'en', 'name' => 'Watermelon'],
            ['lang' => 'de', 'name' => 'Wassermelone'],
            ['lang' => 'fr', 'name' => 'Pastèque'],
            ['lang' => 'es', 'name' => 'Sandía'],
        ]);
        $plant->setQualityGrade('draft');
        $plant->setAiPrefilled(true);
        $plant->setMultiHarvest(false);
        $plant->setHasDormant(false);
        $plant->setCycleDaysMin(70);
        $plant->setCycleDaysMax(90);
        $plant->setYieldPotential('high');
        $plant->setCompletenessScore(78);
        $this->em->persist($plant);

        // Compatibilities
        foreach (['dwc', 'nft', 'kratky', 'drip', 'soil', 'coco'] as $slug) {
            $compat = new GrowSystemCompatibility();
            $compat->setPlant($plant)->setGrowSystem($gs[$slug]);
            $this->em->persist($compat);
        }

        // ── Hydroponic StageParams ──────────────────────────────────────────
        // Threshold structure per parameter:
        // 'ph' => [optimal_min, optimal_max]
        // 'ph_warn' => [warn_min, warn_max]   — amber zone
        // 'ph_crit' => [crit_min, crit_max]   — red / lethal zone
        $hydroStages = [
            'germinating' => [
                'days' => [3, 10],
                'ph' => [5.8, 6.2], 'ph_warn' => [5.5, 6.5], 'ph_crit' => [5.0, 7.0], 'ph_tol' => 72,
                'ec' => [0.8, 1.2], 'ec_warn' => [0.5, 1.5], 'ec_crit' => [0.3, 2.0], 'ec_tol' => 48,
                'tds' => [400, 600],
                'water_temp' => [22, 26], 'water_warn' => [20, 28], 'water_crit' => [16, 32], 'water_tol' => 6,
                'air_temp' => [26, 30], 'air_warn' => [22, 34], 'air_crit' => [18, 38], 'air_tol' => 4,
                'humidity' => [80, 90], 'hum_warn' => [70, 95], 'hum_crit' => [55, 99], 'hum_tol' => 24,
                'ppfd' => [50, 150], 'dli' => [4, 8], 'photo' => 16, 'vpd' => [0.4, 0.7],
                'n' => 50, 'p' => 40, 'k' => 50, 'ca' => 60, 'mg' => 20, 's' => 15,
            ],
            'seedling' => [
                'days' => [7, 14],
                'ph' => [5.8, 6.2], 'ph_warn' => [5.5, 6.5], 'ph_crit' => [5.0, 7.0], 'ph_tol' => 48,
                'ec' => [1.2, 1.8], 'ec_warn' => [0.8, 2.2], 'ec_crit' => [0.5, 2.8], 'ec_tol' => 36,
                'tds' => [600, 900],
                'water_temp' => [20, 24], 'water_warn' => [18, 26], 'water_crit' => [14, 30], 'water_tol' => 4,
                'air_temp' => [24, 28], 'air_warn' => [20, 32], 'air_crit' => [16, 38], 'air_tol' => 2,
                'humidity' => [65, 75], 'hum_warn' => [55, 85], 'hum_crit' => [40, 95], 'hum_tol' => 18,
                'ppfd' => [200, 400], 'dli' => [12, 18], 'photo' => 18, 'vpd' => [0.5, 0.9],
                'n' => 100, 'p' => 60, 'k' => 100, 'ca' => 100, 'mg' => 35, 's' => 30,
            ],
            'vegetative' => [
                'days' => [14, 28],
                'ph' => [5.8, 6.2], 'ph_warn' => [5.5, 6.5], 'ph_crit' => [4.9, 7.2], 'ph_tol' => 48,
                'ec' => [1.8, 2.5], 'ec_warn' => [1.2, 3.0], 'ec_crit' => [0.8, 3.8], 'ec_tol' => 24,
                'tds' => [900, 1250],
                'water_temp' => [18, 22], 'water_warn' => [16, 24], 'water_crit' => [12, 28], 'water_tol' => 4,
                'air_temp' => [24, 28], 'air_warn' => [18, 34], 'air_crit' => [14, 40], 'air_tol' => 1,
                'humidity' => [60, 70], 'hum_warn' => [50, 80], 'hum_crit' => [35, 92], 'hum_tol' => 12,
                'ppfd' => [400, 700], 'dli' => [20, 30], 'photo' => 18, 'vpd' => [0.8, 1.2],
                'n' => 180, 'p' => 60, 'k' => 180, 'ca' => 160, 'mg' => 45, 's' => 50,
            ],
            'flowering' => [
                'days' => [14, 21],
                'ph' => [5.8, 6.2], 'ph_warn' => [5.5, 6.5], 'ph_crit' => [4.9, 7.2], 'ph_tol' => 36,
                'ec' => [2.0, 2.8], 'ec_warn' => [1.5, 3.2], 'ec_crit' => [1.0, 4.0], 'ec_tol' => 18,
                'tds' => [1000, 1400],
                'water_temp' => [18, 22], 'water_warn' => [16, 24], 'water_crit' => [12, 28], 'water_tol' => 3,
                'air_temp' => [22, 27], 'air_warn' => [18, 32], 'air_crit' => [13, 40], 'air_tol' => 0.5,
                'humidity' => [50, 65], 'hum_warn' => [40, 75], 'hum_crit' => [30, 88], 'hum_tol' => 10,
                'ppfd' => [500, 800], 'dli' => [25, 35], 'photo' => 12, 'vpd' => [1.0, 1.5],
                'n' => 120, 'p' => 100, 'k' => 220, 'ca' => 180, 'mg' => 50, 's' => 60,
            ],
            'fruiting' => [
                'days' => [30, 50],
                'ph' => [5.8, 6.2], 'ph_warn' => [5.5, 6.6], 'ph_crit' => [4.9, 7.3], 'ph_tol' => 36,
                'ec' => [2.2, 3.0], 'ec_warn' => [1.5, 3.5], 'ec_crit' => [1.0, 4.2], 'ec_tol' => 18,
                'tds' => [1100, 1500],
                'water_temp' => [18, 22], 'water_warn' => [16, 25], 'water_crit' => [12, 29], 'water_tol' => 3,
                'air_temp' => [24, 30], 'air_warn' => [18, 35], 'air_crit' => [13, 42], 'air_tol' => 0.5,
                'humidity' => [50, 65], 'hum_warn' => [40, 75], 'hum_crit' => [28, 88], 'hum_tol' => 8,
                'ppfd' => [600, 900], 'dli' => [28, 40], 'photo' => 12, 'vpd' => [1.2, 1.6],
                'n' => 100, 'p' => 80, 'k' => 280, 'ca' => 200, 'mg' => 60, 's' => 65,
            ],
            'harvesting' => [
                'days' => [3, 7],
                'ph' => [5.8, 6.2], 'ph_warn' => [5.5, 6.6], 'ph_crit' => [4.9, 7.3], 'ph_tol' => 48,
                'ec' => [1.5, 2.0], 'ec_warn' => [1.0, 2.5], 'ec_crit' => [0.8, 3.2], 'ec_tol' => 24,
                'tds' => [750, 1000],
                'water_temp' => [18, 22], 'water_warn' => [15, 25], 'water_crit' => [11, 29], 'water_tol' => 4,
                'air_temp' => [22, 28], 'air_warn' => [16, 33], 'air_crit' => [12, 40], 'air_tol' => 1,
                'humidity' => [50, 60], 'hum_warn' => [40, 72], 'hum_crit' => [28, 85], 'hum_tol' => 12,
                'ppfd' => [400, 600], 'dli' => [18, 25], 'photo' => 12, 'vpd' => [1.0, 1.4],
                'n' => 60, 'p' => 40, 'k' => 180, 'ca' => 150, 'mg' => 40, 's' => 45,
            ],
        ];

        foreach ($hydroStages as $stage => $p) {
            $sp = $this->buildStageParams($plant, $stage, $p);
            $sp->setGrowSystem($gs['dwc']); // representative hydro system
            $this->em->persist($sp);
        }

        // ── Soil StageParams ────────────────────────────────────────────────
        // Soil: no EC/TDS, higher pH range, lower PPFD outdoors, different NPK
        $soilStages = [
            'germinating' => [
                'days' => [5, 14],
                'ph' => [6.0, 7.0], 'ph_warn' => [5.5, 7.3], 'ph_crit' => [5.0, 7.8],
                'ec' => null, 'ec_warn' => null, 'ec_crit' => null, 'tds' => null, 'water_temp' => null,
                'air_temp' => [25, 30], 'air_warn' => [20, 35], 'air_crit' => [16, 40],
                'humidity' => [75, 90], 'ppfd' => [100, 300], 'dli' => [8, 14], 'photo' => 16, 'vpd' => [0.4, 0.8],
                'n' => 30, 'p' => 30, 'k' => 30, 'ca' => 40, 'mg' => 15, 's' => 10,
            ],
            'seedling' => [
                'days' => [10, 21],
                'ph' => [6.0, 7.0], 'ph_warn' => [5.5, 7.3], 'ph_crit' => [5.0, 7.8],
                'ec' => null, 'ec_warn' => null, 'ec_crit' => null, 'tds' => null, 'water_temp' => null,
                'air_temp' => [22, 28], 'air_warn' => [18, 33], 'air_crit' => [14, 38],
                'humidity' => [60, 75], 'ppfd' => [300, 600], 'dli' => [14, 22], 'photo' => 18, 'vpd' => [0.6, 1.0],
                'n' => 80, 'p' => 50, 'k' => 80, 'ca' => 80, 'mg' => 25, 's' => 20,
            ],
            'vegetative' => [
                'days' => [21, 35],
                'ph' => [6.0, 7.0], 'ph_warn' => [5.5, 7.3], 'ph_crit' => [5.0, 7.8],
                'ec' => null, 'ec_warn' => null, 'ec_crit' => null, 'tds' => null, 'water_temp' => null,
                'air_temp' => [22, 30], 'air_warn' => [17, 35], 'air_crit' => [12, 42],
                'humidity' => [55, 70], 'ppfd' => [500, 900], 'dli' => [22, 35], 'photo' => 18, 'vpd' => [0.8, 1.3],
                'n' => 150, 'p' => 50, 'k' => 150, 'ca' => 130, 'mg' => 40, 's' => 35,
            ],
            'flowering' => [
                'days' => [14, 21],
                'ph' => [6.0, 7.0], 'ph_warn' => [5.5, 7.3], 'ph_crit' => [5.0, 7.8],
                'ec' => null, 'ec_warn' => null, 'ec_crit' => null, 'tds' => null, 'water_temp' => null,
                'air_temp' => [20, 28], 'air_warn' => [16, 33], 'air_crit' => [11, 40],
                'humidity' => [45, 65], 'ppfd' => [600, 1000], 'dli' => [28, 40], 'photo' => 12, 'vpd' => [1.0, 1.6],
                'n' => 80, 'p' => 80, 'k' => 200, 'ca' => 150, 'mg' => 45, 's' => 45,
            ],
            'fruiting' => [
                'days' => [35, 55],
                'ph' => [6.0, 7.0], 'ph_warn' => [5.5, 7.3], 'ph_crit' => [5.0, 7.8],
                'ec' => null, 'ec_warn' => null, 'ec_crit' => null, 'tds' => null, 'water_temp' => null,
                'air_temp' => [22, 32], 'air_warn' => [17, 37], 'air_crit' => [11, 44],
                'humidity' => [45, 65], 'ppfd' => [700, 1100], 'dli' => [30, 45], 'photo' => 12, 'vpd' => [1.2, 1.8],
                'n' => 60, 'p' => 60, 'k' => 250, 'ca' => 170, 'mg' => 55, 's' => 50,
            ],
            'harvesting' => [
                'days' => [3, 7],
                'ph' => [6.0, 7.0], 'ph_warn' => [5.5, 7.3], 'ph_crit' => [5.0, 7.8],
                'ec' => null, 'ec_warn' => null, 'ec_crit' => null, 'tds' => null, 'water_temp' => null,
                'air_temp' => [20, 30], 'air_warn' => [15, 35], 'air_crit' => [10, 42],
                'humidity' => [40, 60], 'ppfd' => [400, 700], 'dli' => [18, 28], 'photo' => 12, 'vpd' => [1.0, 1.5],
                'n' => 40, 'p' => 30, 'k' => 160, 'ca' => 120, 'mg' => 35, 's' => 30,
            ],
        ];

        foreach ($soilStages as $stage => $p) {
            $sp = $this->buildStageParams($plant, $stage, $p);
            $sp->setGrowSystem($gs['soil']);
            $this->em->persist($sp);
        }

        $this->em->flush();
        $output->writeln('<info>Citrullus lanatus seeded (hydroponic + soil).</info>');

        return Command::SUCCESS;
    }

    private function buildStageParams(Plant $plant, string $stage, array $p): StageParams
    {
        $sp = new StageParams();
        $sp->setPlant($plant)->setStage($stage);

        if (isset($p['days'])) { $sp->setStageDaysMin($p['days'][0])->setStageDaysMax($p['days'][1]); }
        if ($p['ph'])         { $sp->setPhMin((string)$p['ph'][0])->setPhMax((string)$p['ph'][1]); }
        if ($p['ph_warn'])    { $sp->setPhWarnMin((string)$p['ph_warn'][0])->setPhWarnMax((string)$p['ph_warn'][1]); }
        if ($p['ph_crit'])    { $sp->setPhCritMin((string)$p['ph_crit'][0])->setPhCritMax((string)$p['ph_crit'][1]); }
        if ($p['ec'])         { $sp->setEcMin((string)$p['ec'][0])->setEcMax((string)$p['ec'][1]); }
        if ($p['ec_warn'])    { $sp->setEcWarnMin((string)$p['ec_warn'][0])->setEcWarnMax((string)$p['ec_warn'][1]); }
        if ($p['ec_crit'])    { $sp->setEcCritMin((string)$p['ec_crit'][0])->setEcCritMax((string)$p['ec_crit'][1]); }
        if ($p['tds'])        { $sp->setTdsMin($p['tds'][0])->setTdsMax($p['tds'][1]); }
        if ($p['water_temp'])  { $sp->setWaterTempMin((string)$p['water_temp'][0])->setWaterTempMax((string)$p['water_temp'][1]); }
        if (!empty($p['water_warn'])) { $sp->setWaterTempWarnMin((string)$p['water_warn'][0])->setWaterTempWarnMax((string)$p['water_warn'][1]); }
        if (!empty($p['water_crit'])) { $sp->setWaterTempCritMin((string)$p['water_crit'][0])->setWaterTempCritMax((string)$p['water_crit'][1]); }
        $sp->setAirTempMin((string)$p['air_temp'][0])->setAirTempMax((string)$p['air_temp'][1]);
        if ($p['air_warn'])   { $sp->setAirTempWarnMin((string)$p['air_warn'][0])->setAirTempWarnMax((string)$p['air_warn'][1]); }
        if ($p['air_crit'])   { $sp->setAirTempCritMin((string)$p['air_crit'][0])->setAirTempCritMax((string)$p['air_crit'][1]); }
        $sp->setHumidityMin($p['humidity'][0])->setHumidityMax($p['humidity'][1]);
        if (!empty($p['hum_warn'])) { $sp->setHumidityWarnMin($p['hum_warn'][0])->setHumidityWarnMax($p['hum_warn'][1]); }
        if (!empty($p['hum_crit'])) { $sp->setHumidityCritMin($p['hum_crit'][0])->setHumidityCritMax($p['hum_crit'][1]); }
        $sp->setPpfdMin($p['ppfd'][0])->setPpfdMax($p['ppfd'][1]);
        $sp->setDliMin((string)$p['dli'][0])->setDliMax((string)$p['dli'][1]);
        $sp->setPhotoperiodHours($p['photo']);
        $sp->setVpdMin((string)$p['vpd'][0])->setVpdMax((string)$p['vpd'][1]);
        $sp->setNPpm($p['n'])->setPPpm($p['p'])->setKPpm($p['k']);
        if ($p['ca']) { $sp->setCaPpm($p['ca']); }
        if ($p['mg']) { $sp->setMgPpm($p['mg']); }
        if ($p['s'])  { $sp->setSPpm($p['s']); }

        if (isset($p['ph_tol']))    { $sp->setPhCritToleranceHours((string)$p['ph_tol']); }
        if (isset($p['ec_tol']))    { $sp->setEcCritToleranceHours((string)$p['ec_tol']); }
        if (isset($p['water_tol'])) { $sp->setWaterTempCritToleranceHours((string)$p['water_tol']); }
        if (isset($p['air_tol']))   { $sp->setAirTempCritToleranceHours((string)$p['air_tol']); }
        if (isset($p['hum_tol']))   { $sp->setHumidityCritToleranceHours((string)$p['hum_tol']); }

        return $sp;
    }
}
