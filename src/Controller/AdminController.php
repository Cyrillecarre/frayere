<?php

namespace App\Controller;

use App\Repository\PosteFourRepository;
use App\Repository\PosteOneRepository;
use App\Repository\PosteThreeRepository;
use App\Repository\PosteTwoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(PosteOneRepository $posteOneRepository, PosteTwoRepository $posteTwoRepository, PosteThreeRepository $posteThreeRepository, PosteFourRepository $posteFourRepository): Response
    {
        $poste_one = $posteOneRepository->findAll();
        $poste_two = $posteTwoRepository->findAll();
        $poste_three = $posteThreeRepository->findAll();
        $poste_four = $posteFourRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'poste_ones' => $poste_one,
            'poste_twos' => $poste_two,
            'poste_threes' => $poste_three,
            'poste_fours' => $poste_four,
        ]);
    }
}
