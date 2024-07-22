<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PosteOneRepository;
use App\Repository\PosteTwoRepository;
use App\Repository\PosteThreeRepository;
use App\Repository\PosteFourRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'app_reservation')]
    public function index(PosteOneRepository $posteOne, PosteTwoRepository $posteTwo, PosteThreeRepository $posteThree, PosteFourRepository $posteFour): Response
    {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_logout');    
        }
        $rdvs = [];
        $repositories = [$posteOne, $posteTwo, $posteThree, $posteFour];

        foreach ($repositories as $repository) {
            $events = $repository->findAll();
            foreach ($events as $event) {
                $startHour = (int)$event->getStart()->format('H');
                $endHour = (int)$event->getEnd()->format('H');

                $classNames = [];
                if ($startHour === 14) {
                    $classNames[] = 'event-start-late';
                }
                if ($endHour === 11) {
                    $classNames[] = 'event-end-early';
                }

                $rdvs[] = [
                    'id' => $event->getId(),
                    'start' => $event->getStart()->format('Y-m-d H:i:s'),
                    'end' => $event->getEnd()->format('Y-m-d H:i:s'),
                    'title' => $event->getTitle(),
                    'backgroundColor' => $event->getBackgroundColor(),
                    'classNames' => $classNames,
                ];
            }
        }

        $data = json_encode($rdvs);

        return $this->render('reservation/index.html.twig', [
            'data' => $data,
            ]);
    }
    
}
