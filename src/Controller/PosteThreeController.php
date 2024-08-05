<?php

namespace App\Controller;

use App\Entity\PosteThree;
use App\Form\PosteThreeType;
use App\Repository\PosteThreeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PricingService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/poste/three')]
class PosteThreeController extends AbstractController
{
    #[Route('/', name: 'app_poste_three_index', methods: ['GET'])]
    public function index(PosteThreeRepository $posteThreeRepository): Response
    {
        return $this->render('poste_three/index.html.twig', [
            'poste_threes' => $posteThreeRepository->findAll(),
        ]);
    }

    private $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    #[Route('/new', name: 'app_poste_three_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, PosteThreeRepository $posteThreeRepository, SessionInterface $session): Response
    {
        $posteThree = new PosteThree();
        $form = $this->createForm(PosteThreeType::class, $posteThree);
        $form->handleRequest($request);

        $totalPrice = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $posteThree->getStart();
            $end = $posteThree->getEnd();

            $duration = ($end->getTimestamp() - $start->getTimestamp()) / (60 * 60 * 24);

            if ($duration < 1.7) {
                return $this->redirectToRoute('app_poste_one_error'); 
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
                return $this->redirectToRoute('app_poste_three_error');
            }

            $overlappingEvents = $posteThreeRepository->findOverlappingEvents($start, $end);

            if (count($overlappingEvents) > 0) {
                return $this->redirectToRoute('app_poste_three_error');
            } else {
                $entityManager->persist($posteThree);
                $entityManager->flush();
                $numFishers = $form->get('numberOfFishers')->getData();
                $pellets = $form->get('pellets')->getData();
                $graines = $form->get('graines')->getData();
                
                try {
                    $totalPrice = $this->pricingService->calculatePrice($numNights, $numFishers, [
                        'pellets' => $pellets,
                        'graines' => $graines
                    ]);
                } catch (\InvalidArgumentException $e) {
                    return $this->redirectToRoute('app_poste_three_error');
                }

                $session->set('reservation_details', [
                    'posteId' => $posteThree->getId(),
                    'poste_title' => 'Poste 3',
                    'poste_type' => 'trois',
                    'start' => $posteThree->getStart(),
                    'end' => $posteThree->getEnd(),
                    'numberOfFishers' => $form->get('numberOfFishers')->getData(),
                    'pellets' => $form->get('pellets')->getData(),
                    'graines' => $form->get('graines')->getData(),
                    'email' => $form->get('email')->getData(),
                    'phoneNumber' => $form->get('phoneNumber')->getData(),
                ]);
                
                return $this->redirectToRoute('app_poste_three_prix', [
                    'totalPrice' => $totalPrice,
                    'numNights' => $numNights,
                    'numFishers' => $numFishers,
                    'pellets' => $pellets,
                    'graines' => $graines,
                    'poste_id' => $posteThree->getId(),
                    'poste_type' => 'trois',
                    'start' => $start->format('d-m'),
                    'end' => $end->format('d-m'),
                ]);
            }
        }

        return $this->render('poste_three/new.html.twig', [
            'poste_three' => $posteThree,
            'form' => $form,
        ]);
    }

    #[Route('/prix', name: 'app_poste_three_prix', methods: ['GET'])]
    public function summary(Request $request): Response
    {
        $stripePublicKey = $this->getParameter('stripe_public_key');
        $totalPrice = $request->query->get('totalPrice');
        $numNights = $request->query->get('numNights');
        $numFishers = $request->query->get('numFishers');
        $pellets = $request->query->get('pellets');
        $graines = $request->query->get('graines');
        $posteId = $request->query->get('poste_id');
        $posteType = $request->query->get('poste_type');
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->render('poste_three/prix.html.twig', [
            'totalPrice' => $totalPrice,
            'numNights' => $numNights,
            'numFishers' => $numFishers,
            'pellets' => $pellets,
            'graines' => $graines,
            'stripe_public_key' => $stripePublicKey,
            'poste_id' => $posteId,
            'poste_type' => $posteType,
            'start' => $start,
            'end' => $end,
        ]);
    }

    #[Route('/poste/three/error', name: 'app_poste_three_error', methods: ['GET'])]
    public function error(): Response
    {
        return $this->render('poste_three/error.html.twig');
    }

    #[Route('/{id}', name: 'app_poste_three_show', methods: ['GET'])]
    public function show(PosteThree $posteThree): Response
    {
        return $this->render('poste_three/show.html.twig', [
            'poste_three' => $posteThree,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_approve_reservation_three')]
    public function approveReservation(PosteThree $posteThree, EntityManagerInterface $entityManager): Response
    {
        $posteThree->setApprouved(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/{id}/edit', name: 'app_poste_three_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PosteThree $posteThree, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PosteThreeType::class, $posteThree);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('poste_three/edit.html.twig', [
            'poste_three' => $posteThree,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_poste_three_delete', methods: ['POST'])]
    public function delete(Request $request, PosteThree $posteThree, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$posteThree->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($posteThree);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_poste_three_index', [], Response::HTTP_SEE_OTHER);
    }
}
