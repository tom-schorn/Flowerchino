<?php

namespace App\Controller;

use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StaticController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function homepage(PlantRepository $plants): Response
    {
        $result = $plants->findPaginated(1, 6);

        return $this->render('static/homepage.html.twig', [
            'plant_count'  => $plants->count([]),
            'recent_plants' => $result['items'],
        ]);
    }

    #[Route('/robots.txt', name: 'robots_txt')]
    public function robotsTxt(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /v1/',
            'Disallow: /api/',
            '',
            'Sitemap: ' . $this->generateUrl('sitemap_xml', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }

    #[Route('/sitemap.xml', name: 'sitemap_xml')]
    public function sitemap(PlantRepository $plants): Response
    {
        $allPlants = $plants->findAll();

        $urls = [];

        // Static pages
        $urls[] = ['loc' => $this->generateUrl('homepage',     [], UrlGeneratorInterface::ABSOLUTE_URL), 'changefreq' => 'weekly',  'priority' => '1.0'];
        $urls[] = ['loc' => $this->generateUrl('plant_index',  [], UrlGeneratorInterface::ABSOLUTE_URL), 'changefreq' => 'daily',   'priority' => '0.9'];
        $urls[] = ['loc' => $this->generateUrl('plant_request',[], UrlGeneratorInterface::ABSOLUTE_URL), 'changefreq' => 'monthly', 'priority' => '0.6'];
        $urls[] = ['loc' => $this->generateUrl('api_docs',     [], UrlGeneratorInterface::ABSOLUTE_URL), 'changefreq' => 'monthly', 'priority' => '0.7'];

        // Plant detail pages (only non-pending)
        foreach ($allPlants as $plant) {
            if ($plant->getQualityGrade() === 'pending') continue;
            $urls[] = [
                'loc'        => $this->generateUrl('plant_detail', ['slug' => $plant->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod'    => $plant->getUpdatedAt()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        $xml = $this->renderView('static/sitemap.xml.twig', ['urls' => $urls]);

        return new Response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
