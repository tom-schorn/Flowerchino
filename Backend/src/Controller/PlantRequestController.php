<?php

namespace App\Controller;

use App\Entity\Plant;
use App\Message\FillPlantMessage;
use App\Repository\PlantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
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
        MessageBusInterface $bus,
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

        // Dispatch AI fill job
        $bus->dispatch(new FillPlantMessage($plant->getId(), $gbifKey, $canonicalName));

        return $this->redirectToRoute('plant_pending', ['id' => $plant->getId()]);
    }

    #[Route('/plants/pending/{id}', name: 'plant_pending', requirements: ['id' => '\d+'])]
    public function pending(int $id, PlantRepository $plants): Response
    {
        $plant = $plants->find($id);
        if (!$plant) {
            throw $this->createNotFoundException("Plant #$id not found");
        }

        // Already done → redirect to detail
        if ($plant->getQualityGrade() !== 'pending') {
            return $this->redirectToRoute('plant_detail', ['slug' => $plant->getSlug()]);
        }

        return $this->render('plant/pending.html.twig', ['plant' => $plant]);
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
