<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthWebController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }
}
