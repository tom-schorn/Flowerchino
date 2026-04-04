<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DocsController extends AbstractController
{
    #[Route('/developers', name: 'api_docs')]
    public function index(): Response
    {
        return $this->render('docs/api.html.twig');
    }
}
