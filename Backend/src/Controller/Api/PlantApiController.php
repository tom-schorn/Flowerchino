<?php

namespace App\Controller\Api;

use App\Entity\Plant;
use App\Entity\StageParams;
use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1/plants', name: 'api_plants_')]
class PlantApiController extends AbstractController
{
    private const PER_PAGE_MAX = 100;
    private const PER_PAGE_DEFAULT = 20;

    // ── List & search ────────────────────────────────────────────────────

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, PlantRepository $plants): JsonResponse
    {
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = min(self::PER_PAGE_MAX, max(1, (int) $request->query->get('per_page', self::PER_PAGE_DEFAULT)));

        $result = $plants->findPaginated($page, $perPage);

        return $this->envelope(
            array_map(fn(Plant $p) => $this->serializePlantSummary($p), $result['items']),
            meta: [
                'total'        => $result['total'],
                'per_page'     => $perPage,
                'current_page' => $page,
                'total_pages'  => (int) ceil($result['total'] / $perPage),
            ]
        );
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, PlantRepository $plants): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return $this->error('Query parameter "q" is required.', 400);
        }

        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = min(self::PER_PAGE_MAX, max(1, (int) $request->query->get('per_page', self::PER_PAGE_DEFAULT)));

        $result = $plants->search($q, $page, $perPage);

        return $this->envelope(
            array_map(fn(Plant $p) => $this->serializePlantSummary($p), $result['items']),
            meta: [
                'total'        => $result['total'],
                'per_page'     => $perPage,
                'current_page' => $page,
                'total_pages'  => (int) ceil($result['total'] / $perPage),
                'query'        => $q,
            ]
        );
    }

    // ── Lookup by external identifier ────────────────────────────────────

    #[Route('/by-slug/{slug}', name: 'by_slug', methods: ['GET'])]
    public function bySlug(string $slug, PlantRepository $plants): JsonResponse
    {
        $plant = $plants->findBySlug($slug);
        return $plant ? $this->envelope($this->serializePlant($plant)) : $this->notFound("Plant not found: $slug");
    }

    #[Route('/by-gbif/{gbifKey}', name: 'by_gbif', methods: ['GET'])]
    public function byGbif(int $gbifKey, PlantRepository $plants): JsonResponse
    {
        $plant = $plants->findByGbifKey($gbifKey);
        return $plant ? $this->envelope($this->serializePlant($plant)) : $this->notFound("No plant found for GBIF key $gbifKey");
    }

    #[Route('/by-ipni/{ipniId}', name: 'by_ipni', methods: ['GET'])]
    public function byIpni(string $ipniId, PlantRepository $plants): JsonResponse
    {
        $plant = $plants->findByIpniId($ipniId);
        return $plant ? $this->envelope($this->serializePlant($plant)) : $this->notFound("No plant found for IPNI ID $ipniId");
    }

    // ── Single plant ─────────────────────────────────────────────────────

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, PlantRepository $plants): JsonResponse
    {
        $plant = $plants->find($id);
        return $plant ? $this->envelope($this->serializePlant($plant)) : $this->notFound("Plant #$id not found");
    }

    #[Route('/{id}/status', name: 'status', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function status(int $id, PlantRepository $plants): JsonResponse
    {
        $plant = $plants->find($id);
        if (!$plant) {
            return $this->notFound("Plant #$id not found");
        }

        return $this->envelope([
            'id'                  => $plant->getId(),
            'quality_grade'       => $plant->getQualityGrade(),
            'ai_prefilled'        => $plant->isAiPrefilled(),
            'community_verified'  => $plant->isCommunityVerified(),
            'completeness_score'  => $plant->getCompletenessScore(),
            'last_reviewed_at'    => $plant->getLastReviewedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    // ── Stage params ──────────────────────────────────────────────────────

    #[Route('/{id}/params', name: 'params', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function params(int $id, Request $request, PlantRepository $plants): JsonResponse
    {
        $plant = $plants->find($id);
        if (!$plant) {
            return $this->notFound("Plant #$id not found");
        }

        $filterStage  = $request->query->get('stage');
        $filterSystem = $request->query->get('system');

        $params = $plant->getStageParams()->filter(function (StageParams $sp) use ($filterStage, $filterSystem) {
            if ($filterStage && $sp->getStage() !== $filterStage) {
                return false;
            }
            if ($filterSystem && $sp->getGrowSystem()?->getSlug() !== $filterSystem) {
                return false;
            }
            return true;
        });

        return $this->envelope(
            array_values(array_map(
                fn(StageParams $sp) => $this->serializeStageParams($sp),
                $params->toArray()
            ))
        );
    }

    // ── Serializers ───────────────────────────────────────────────────────

    private function serializePlantSummary(Plant $plant): array
    {
        return [
            'id'               => $plant->getId(),
            'canonical_name'   => $plant->getCanonicalName(),
            'scientific_name'  => $plant->getScientificName(),
            'slug'             => $plant->getSlug(),
            'common_names'     => $plant->getCommonNames(),
            'family'           => $plant->getFamily(),
            'genus'            => $plant->getGenus(),
            'gbif_key'         => $plant->getGbifKey(),
            'quality_grade'    => $plant->getQualityGrade(),
            'completeness'     => $plant->getCompletenessScore(),
        ];
    }

    private function serializePlant(Plant $plant): array
    {
        return [
            'id'                  => $plant->getId(),
            'canonical_name'      => $plant->getCanonicalName(),
            'scientific_name'     => $plant->getScientificName(),
            'authorship'          => $plant->getAuthorship(),
            'slug'                => $plant->getSlug(),
            'common_names'        => $plant->getCommonNames(),
            'taxonomy'            => [
                'kingdom' => $plant->getKingdom(),
                'phylum'  => $plant->getPhylum(),
                'class'   => $plant->getClass(),
                'order'   => $plant->getOrder(),
                'family'  => $plant->getFamily(),
                'genus'   => $plant->getGenus(),
                'species' => $plant->getSpecies(),
                'rank'    => $plant->getRank(),
                'status'  => $plant->getTaxonomicStatus(),
            ],
            'identifiers'         => [
                'gbif_key' => $plant->getGbifKey(),
                'ipni_id'  => $plant->getIpniId(),
            ],
            'growth'              => [
                'cycle_days_min'  => $plant->getCycleDaysMin(),
                'cycle_days_max'  => $plant->getCycleDaysMax(),
                'multi_harvest'   => $plant->isMultiHarvest(),
                'has_dormant'     => $plant->isHasDormant(),
                'yield_potential' => $plant->getYieldPotential(),
            ],
            'compatible_systems'  => array_values(array_map(
                fn($c) => [
                    'slug' => $c->getGrowSystem()->getSlug(),
                    'name' => $c->getGrowSystem()->getName(),
                    'type' => $c->getGrowSystem()->getType(),
                ],
                $plant->getGrowSystemCompatibilities()->toArray()
            )),
            'quality'             => [
                'grade'              => $plant->getQualityGrade(),
                'ai_prefilled'       => $plant->isAiPrefilled(),
                'community_verified' => $plant->isCommunityVerified(),
                'completeness_score' => $plant->getCompletenessScore(),
                'last_reviewed_at'   => $plant->getLastReviewedAt()?->format(\DateTimeInterface::ATOM),
            ],
            'created_at'          => $plant->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at'          => $plant->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function serializeStageParams(StageParams $sp): array
    {
        $gs = $sp->getGrowSystem();

        return [
            'id'         => $sp->getId(),
            'stage'      => $sp->getStage(),
            'grow_system' => $gs ? [
                'slug' => $gs->getSlug(),
                'name' => $gs->getName(),
                'type' => $gs->getType(),
            ] : null,
            'stage_days' => [
                'min' => $sp->getStageDaysMin(),
                'max' => $sp->getStageDaysMax(),
            ],
            'water' => [
                'ph' => $this->thresholdField($sp->getPhMin(), $sp->getPhMax(), $sp->getPhWarnMin(), $sp->getPhWarnMax(), $sp->getPhCritMin(), $sp->getPhCritMax(), $sp->getPhCritToleranceHours()),
                'ec_ms_cm' => $this->thresholdField($sp->getEcMin(), $sp->getEcMax(), $sp->getEcWarnMin(), $sp->getEcWarnMax(), $sp->getEcCritMin(), $sp->getEcCritMax(), $sp->getEcCritToleranceHours()),
                'tds_ppm' => $sp->getTdsMin() !== null ? ['min' => $sp->getTdsMin(), 'max' => $sp->getTdsMax()] : null,
                'water_temp_c' => $this->thresholdField($sp->getWaterTempMin(), $sp->getWaterTempMax(), $sp->getWaterTempWarnMin(), $sp->getWaterTempWarnMax(), $sp->getWaterTempCritMin(), $sp->getWaterTempCritMax(), $sp->getWaterTempCritToleranceHours()),
                'dissolved_oxygen_min_mg_l' => $sp->getDissolvedOxygenMin() !== null ? (float) $sp->getDissolvedOxygenMin() : null,
                'nutrients_ppm' => [
                    'n' => $sp->getNPpm(), 'p' => $sp->getPPpm(), 'k' => $sp->getKPpm(),
                    'ca' => $sp->getCaPpm(), 'mg' => $sp->getMgPpm(), 's' => $sp->getSPpm(),
                ],
            ],
            'environment' => [
                'air_temp_c' => $this->thresholdField($sp->getAirTempMin(), $sp->getAirTempMax(), $sp->getAirTempWarnMin(), $sp->getAirTempWarnMax(), $sp->getAirTempCritMin(), $sp->getAirTempCritMax(), $sp->getAirTempCritToleranceHours()),
                'humidity_pct' => $this->thresholdField($sp->getHumidityMin(), $sp->getHumidityMax(), $sp->getHumidityWarnMin(), $sp->getHumidityWarnMax(), $sp->getHumidityCritMin(), $sp->getHumidityCritMax(), $sp->getHumidityCritToleranceHours()),
                'vpd_kpa' => $sp->getVpdMin() !== null ? ['min' => (float) $sp->getVpdMin(), 'max' => (float) $sp->getVpdMax()] : null,
            ],
            'light' => [
                'ppfd_umol' => $sp->getPpfdMin() !== null ? ['min' => $sp->getPpfdMin(), 'max' => $sp->getPpfdMax()] : null,
                'dli_mol_day' => $sp->getDliMin() !== null ? ['min' => (float) $sp->getDliMin(), 'max' => (float) $sp->getDliMax()] : null,
                'photoperiod_hours' => $sp->getPhotoperiodHours(),
            ],
        ];
    }

    private function thresholdField(mixed $optMin, mixed $optMax, mixed $warnMin, mixed $warnMax, mixed $critMin, mixed $critMax, mixed $lethalAfterHours): ?array
    {
        if ($optMin === null) {
            return null;
        }
        return [
            'optimal'            => ['min' => $this->num($optMin), 'max' => $this->num($optMax)],
            'warning'            => ($warnMin !== null) ? ['min' => $this->num($warnMin), 'max' => $this->num($warnMax)] : null,
            'critical'           => ($critMin !== null) ? ['min' => $this->num($critMin), 'max' => $this->num($critMax)] : null,
            'lethal_after_hours' => $lethalAfterHours !== null ? (float) $lethalAfterHours : null,
        ];
    }

    private function num(mixed $v): int|float|null
    {
        if ($v === null) return null;
        $f = (float) $v;
        return $f == (int) $f ? (int) $f : $f;
    }

    // ── Response helpers ──────────────────────────────────────────────────

    private function envelope(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        $body = ['status' => 'success', 'data' => $data, 'error' => null];
        if ($meta) {
            $body['meta'] = $meta;
        }
        return new JsonResponse($body, $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['status' => 'error', 'data' => null, 'error' => $message], $status);
    }

    private function notFound(string $message): JsonResponse
    {
        return $this->error($message, 404);
    }
}
