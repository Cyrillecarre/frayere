<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TarifController extends AbstractController
{
    #[Route('/tarif', name: 'app_tarif')]
    public function index(): Response
    {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_logout');    
        }
        
        return $this->render('tarif/index.html.twig', [
            'controller_name' => 'TarifController',
        ]);
    }
}
