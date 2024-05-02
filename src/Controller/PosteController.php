<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PosteController extends AbstractController
{
    #[Route('/poste', name: 'app_poste')]
    public function index(): Response
    {
        return $this->render('poste/index.html.twig', [
            'controller_name' => 'PosteController',
        ]);
    }
}
