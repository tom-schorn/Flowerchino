<?php

namespace App\Controller;

use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlantController extends AbstractController
{
    private const STAGE_ORDER = ['germinating', 'seedling', 'vegetative', 'flowering', 'fruiting', 'harvesting', 'dormant'];

    #[Route('/plants/{slug}', name: 'plant_detail')]
    public function detail(string $slug, PlantRepository $plants): Response
    {
        $plant = $plants->findBySlug($slug);

        if (!$plant) {
            throw $this->createNotFoundException("Plant not found: $slug");
        }

        // Group stage params by medium type (hydroponic / soil / etc.)
        $paramsByMedium = [];
        foreach ($plant->getStageParams() as $sp) {
            $type = $sp->getGrowSystem()?->getType() ?? 'hydroponic';
            $paramsByMedium[$type][$sp->getStage()] = $sp;
        }

        // Sort each medium's stages
        foreach ($paramsByMedium as $type => &$stages) {
            uksort($stages, fn($a, $b) =>
                array_search($a, self::STAGE_ORDER) <=> array_search($b, self::STAGE_ORDER)
            );
        }

        return $this->render('plant/detail.html.twig', [
            'plant' => $plant,
            'paramsByMedium' => $paramsByMedium,
            'compatibilities' => $plant->getGrowSystemCompatibilities(),
        ]);
    }
}
