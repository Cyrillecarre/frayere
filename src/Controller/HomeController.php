<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PosteOneRepository;
use App\Repository\PosteTwoRepository;
use App\Repository\PosteThreeRepository;
use App\Repository\PosteFourRepository;


class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(posteOneRepository $posteOne, PosteTwoRepository $posteTwo, PosteThreeRepository $posteThree, PosteFourRepository $posteFour): Response
    {
        $events1 = $posteOne->findAll();
        $rdvs = [];
        foreach($events1 as $event1){
            $rdvs[] = [
                'id' => $event1->getId(),
                'start' => $event1->getStart()->format('Y-m-d H:i:s'),
                'end' => $event1->getEnd()->format('Y-m-d H:i:s'),
                'title' => $event1->getTitle(),
                'backgroundColor' => $event1->getBackgroundColor(),
            ];
        }

        $events2 = $posteTwo->findAll();
        foreach($events2 as $event2){
            $rdvs[] = [
                'id' => $event2->getId(),
                'start' => $event2->getStart()->format('Y-m-d H:i:s'),
                'end' => $event2->getEnd()->format('Y-m-d H:i:s'),
                'title' => $event2->getTitle(),
                'backgroundColor' => $event2->getBackgroundColor(),
            ];
        }

        $events3 = $posteThree->findAll();
        foreach($events3 as $event3){
            $rdvs[] = [
                'id' => $event3->getId(),
                'start' => $event3->getStart()->format('Y-m-d H:i:s'),
                'end' => $event3->getEnd()->format('Y-m-d H:i:s'),
                'title' => $event3->getTitle(),
                'backgroundColor' => $event3->getBackgroundColor(),
            ];
        }

        $events4 = $posteFour->findAll();
        foreach($events4 as $event4){
            $rdvs[] = [
                'id' => $event4->getId(),
                'start' => $event4->getStart()->format('Y-m-d H:i:s'),
                'end' => $event4->getEnd()->format('Y-m-d H:i:s'),
                'title' => $event4->getTitle(),
                'backgroundColor' => $event4->getBackgroundColor(),
            ];
        }
        $data = json_encode($rdvs);

        return $this->render('home/index.html.twig', [
            'data' => $data,
            ]);
    }
}