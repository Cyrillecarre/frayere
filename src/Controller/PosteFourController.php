<?php

namespace App\Controller;

use App\Entity\PosteFour;
use App\Form\PosteFourType;
use App\Repository\PosteFourRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\PricingService;

#[Route('/poste/four')]
class PosteFourController extends AbstractController
{
    #[Route('/', name: 'app_poste_four_index', methods: ['GET'])]
    public function index(PosteFourRepository $posteFourRepository): Response
    {
        return $this->render('poste_four/index.html.twig', [
            'poste_fours' => $posteFourRepository->findAll(),
        ]);
    }

    private $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    #[Route('/new', name: 'app_poste_four_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, PosteFourRepository $posteFourRepository): Response
    {
        $posteFour = new PosteFour();
        $form = $this->createForm(PosteFourType::class, $posteFour);
        $form->handleRequest($request);

        $totalPrice = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $posteFour->getStart();
            $end = $posteFour->getEnd();

            $duration = ($end->getTimestamp() - $start->getTimestamp()) / (60 * 60 * 24);

            if ($duration < 1.7) {
                return $this->redirectToRoute('app_poste_four_error'); 
            } elseif ($duration <= 2.7) {
                $numNights = 2;
            } elseif ($duration <= 3.7) {
                $numNights = 3;
            } elseif ($duration <= 4.7) {
                $numNights = 4;
            } elseif ($duration <= 5.7) {
                $numNights = 5;
            } elseif ($duration <= 6.7) {
                $numNights = 6;
            } elseif ($duration <= 7.7) {
                $numNights = 7;
            } else {
                return $this->redirectToRoute('app_poste_four_error');
            }
            $overlappingEvents = $posteFourRepository->findOverlappingEvents($start, $end);

            if (count($overlappingEvents) > 0) {
                return $this->redirectToRoute('app_poste_four_error');
            } else {
                $numFishers = $form->get('numberOfFishers')->getData();
                $pellets = $form->get('pellets')->getData();
                $graines = $form->get('graines')->getData();
                
                try {
                    $totalPrice = $this->pricingService->calculatePrice($numNights, $numFishers, [
                        'pellets' => $pellets,
                        'graines' => $graines
                    ]);
                } catch (\InvalidArgumentException $e) {
                    // Gestion de l'erreur si les prix ne sont pas définis
                    return $this->redirectToRoute('app_poste_one_error');
                }

                
                return $this->redirectToRoute('app_poste_four_prix', [
                    'totalPrice' => $totalPrice,
                    'start' => $start->format('d-m \à H'),
                    'end' => $end->format('d-m \à H'),
                    'numNights' => $numNights,
                    'numFishers' => $numFishers,
                    'pellets' => $pellets,
                    'graines' => $graines
                ]);
            }
        }

        return $this->render('poste_four/new.html.twig', [
            'poste_four' => $posteFour,
            'form' => $form,
        ]);
    }

    #[Route('/prix', name: 'app_poste_four_prix', methods: ['GET'])]
    public function summary(Request $request): Response
    {
        $totalPrice = $request->query->get('totalPrice');
        $numNights = $request->query->get('numNights');
        $numFishers = $request->query->get('numFishers');
        $pellets = $request->query->get('pellets');
        $graines = $request->query->get('graines');
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->render('poste_four/prix.html.twig', [
            'totalPrice' => $totalPrice,
            'numNights' => $numNights,
            'numFishers' => $numFishers,
            'pellets' => $pellets,
            'graines' => $graines,
            'start' => $start,
            'end' => $end
        ]);
    }

    #[Route('/poste/four/error', name: 'app_poste_four_error', methods: ['GET'])]
    public function error(): Response
    {
        return $this->render('poste_four/error.html.twig');
    }

    #[Route('/{id}', name: 'app_poste_four_show', methods: ['GET'])]
    public function show(PosteFour $posteFour): Response
    {
        return $this->render('poste_four/show.html.twig', [
            'poste_four' => $posteFour,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_approve_reservation_four')]
    public function approveReservation(PosteFour $posteFour, EntityManagerInterface $entityManager): Response
    {
        // Marquez la réservation comme approuvée
        $posteFour->setApprouved(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/{id}/edit', name: 'app_poste_four_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PosteFour $posteFour, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PosteFourType::class, $posteFour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_poste_four_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('poste_four/edit.html.twig', [
            'poste_four' => $posteFour,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_poste_four_delete', methods: ['POST'])]
    public function delete(Request $request, PosteFour $posteFour, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$posteFour->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($posteFour);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }
}
