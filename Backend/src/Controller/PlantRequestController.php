<?php

namespace App\Controller;

use App\Entity\Plant;
use App\Repository\PlantRepository;
use App\Service\PlantFillService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class PlantRequestController extends AbstractController
{
    #[Route('/plants/request', name: 'plant_request', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('plant/request.html.twig');
    }

    #[Route('/plants/request', name: 'plant_request_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        PlantRepository $plants,
        EntityManagerInterface $em,
    ): Response {
        $gbifKey       = (int) $request->request->get('gbif_key');
        $canonicalName = trim((string) $request->request->get('canonical_name'));

        if (!$gbifKey || !$canonicalName) {
            return $this->redirectToRoute('plant_request');
        }

        // Already in DB → go to detail page
        $existing = $plants->findByGbifKey($gbifKey);
        if ($existing) {
            return $this->redirectToRoute('plant_detail', ['slug' => $existing->getSlug()]);
        }

        // Create stub
        $slug  = (new AsciiSlugger())->slug(strtolower($canonicalName))->toString();
        $plant = new Plant();
        $plant->setCanonicalName($canonicalName);
        $plant->setScientificName($canonicalName);
        $plant->setSlug($slug);
        $plant->setGbifKey($gbifKey);
        $plant->setQualityGrade('pending');
        $plant->setAiPrefilled(false);
        $em->persist($plant);
        $em->flush();

        return $this->redirectToRoute('plant_generating', ['id' => $plant->getId()]);
    }

    #[Route('/plants/generating/{id}', name: 'plant_generating', requirements: ['id' => '\d+'])]
    public function generating(int $id, PlantRepository $plants): Response
    {
        $plant = $plants->find($id);
        if (!$plant) {
            throw $this->createNotFoundException("Plant #$id not found");
        }

        if ($plant->getQualityGrade() !== 'pending') {
            return $this->redirectToRoute('plant_detail', ['slug' => $plant->getSlug()]);
        }

        return $this->render('plant/generating.html.twig', ['plant' => $plant]);
    }

    #[Route('/plants/generating/{id}/stream', name: 'plant_generating_stream', requirements: ['id' => '\d+'])]
    public function sseStream(int $id, PlantRepository $plants, PlantFillService $fillService): StreamedResponse
    {
        $plant = $plants->find($id);

        return new StreamedResponse(function () use ($plant, $fillService) {
            set_time_limit(120);

            // Alle Output-Buffer leeren damit SSE-Events sofort raus gehen
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Initiales Ping damit der Browser weiß dass die Verbindung steht
            $this->sse('ping', []);

            if (!$plant || $plant->getQualityGrade() !== 'pending') {
                $this->sse('done', ['slug' => $plant?->getSlug() ?? '']);
                return;
            }

            $fillService->fill($plant, function (string $step, string $label, int $current, int $total) {
                $this->sse('progress', [
                    'step'    => $step,
                    'label'   => $label,
                    'current' => $current,
                    'total'   => $total,
                ]);
            });

            $this->sse('done', ['slug' => $plant->getSlug()]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sse(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    // GBIF proxy — avoids CORS issues calling GBIF from the browser
    #[Route('/api/gbif-suggest', name: 'gbif_suggest', methods: ['GET'])]
    public function gbifSuggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if (strlen($q) < 2) {
            return new JsonResponse([]);
        }

        $url = 'https://api.gbif.org/v1/species/suggest?' . http_build_query([
            'q'        => $q,
            'rank'     => 'SPECIES',
            'kingdom'  => 'Plantae',
            'limit'    => 10,
        ]);

        $ctx  = stream_context_create(['http' => ['timeout' => 5]]);
        $body = @file_get_contents($url, false, $ctx);
        if (!$body) {
            return new JsonResponse([]);
        }

        $results = json_decode($body, true) ?? [];
        $items   = array_map(fn($r) => [
            'key'            => $r['key'],
            'canonical_name' => $r['canonicalName'] ?? $r['scientificName'] ?? '',
            'family'         => $r['family'] ?? null,
            'genus'          => $r['genus'] ?? null,
            'rank'           => $r['rank'] ?? null,
        ], array_filter($results, fn($r) => isset($r['key'], $r['canonicalName'])));

        return new JsonResponse(array_values($items));
    }
}
