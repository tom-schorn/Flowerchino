<?php

namespace App\Controller;

use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlantController extends AbstractController
{
    private const STAGE_ORDER = ['germinating', 'seedling', 'vegetative', 'flowering', 'fruiting', 'harvesting', 'dormant'];

    #[Route('/plants', name: 'plant_index')]
    public function index(Request $request, PlantRepository $plants): Response
    {
        $q       = trim((string) $request->query->get('q', ''));
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 24;

        $result = $q
            ? $plants->search($q, $page, $perPage)
            : $plants->findPaginated($page, $perPage);

        return $this->render('plant/index.html.twig', [
            'plants'      => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($result['total'] / $perPage),
            'q'           => $q,
        ]);
    }

    #[Route('/plants/{slug}', name: 'plant_detail', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)+'])]
    public function detail(string $slug, PlantRepository $plants): Response
    {
        $plant = $plants->findBySlug($slug);

        if (!$plant) {
            throw $this->createNotFoundException("Plant not found: $slug");
        }

        $paramsByMedium = [];
        foreach ($plant->getStageParams() as $sp) {
            $type = $sp->getGrowSystem()?->getType() ?? 'hydroponic';
            $paramsByMedium[$type][$sp->getStage()] = $sp;
        }

        foreach ($paramsByMedium as $type => &$stages) {
            uksort($stages, fn($a, $b) =>
                array_search($a, self::STAGE_ORDER) <=> array_search($b, self::STAGE_ORDER)
            );
        }

        return $this->render('plant/detail.html.twig', [
            'plant'          => $plant,
            'paramsByMedium' => $paramsByMedium,
            'compatibilities' => $plant->getGrowSystemCompatibilities(),
        ]);
    }
}
