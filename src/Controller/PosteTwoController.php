<?php

namespace App\Controller;

use App\Entity\PosteTwo;
use App\Form\PosteTwoType;
use App\Repository\PosteTwoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PricingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/poste/two')]
class PosteTwoController extends AbstractController
{
    #[Route('/', name: 'app_poste_two_index', methods: ['GET'])]
    public function index(PosteTwoRepository $posteTwoRepository): Response
    {
        return $this->render('poste_two/index.html.twig', [
            'poste_twos' => $posteTwoRepository->findAll(),
        ]);
    }

    private $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    #[Route('/new', name: 'app_poste_two_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, PosteTwoRepository $posteTwoRepository, SessionInterface $session): Response
    {
        $posteTwo = new PosteTwo();
        $form = $this->createForm(PosteTwoType::class, $posteTwo);
        $form->handleRequest($request);

        $totalPrice = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $posteTwo->getStart();
            $end = $posteTwo->getEnd();

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
                return $this->redirectToRoute('app_poste_one_error');
            }

            $overlappingEvents = $posteTwoRepository->findOverlappingEvents($start, $end);
            
            if (count($overlappingEvents) > 0) {
                return $this->redirectToRoute('app_poste_two_error');
            } else {
                $entityManager->persist($posteTwo);
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
                    return $this->redirectToRoute('app_poste_two_error');
                }

                $session->set('reservation_details', [
                    'posteId' => $posteTwo->getId(),
                    'poste_title' => 'Poste 2',
                    'poste_type' => 'deux',
                    'start' => $posteTwo->getStart(),
                    'end' => $posteTwo->getEnd(),
                    'numberOfFishers' => $form->get('numberOfFishers')->getData(),
                    'pellets' => $form->get('pellets')->getData(),
                    'graines' => $form->get('graines')->getData(),
                    'email' => $form->get('email')->getData(),
                    'phoneNumber' => $form->get('phoneNumber')->getData(),
                ]);
                
                return $this->redirectToRoute('app_poste_two_prix', [
                    'totalPrice' => $totalPrice,
                    'numNights' => $numNights,
                    'numFishers' => $numFishers,
                    'pellets' => $pellets,
                    'graines' => $graines,
                    'poste_id' => $posteTwo->getId(),
                    'poste_type' => 'deux',
                    'start' => $start->format('d-m'),
                    'end' => $end->format('d-m'),
                ]);
            }
        }

        return $this->render('poste_two/new.html.twig', [
            'poste_two' => $posteTwo,
            'form' => $form,
        ]);
    }

    #[Route('/prix', name: 'app_poste_two_prix', methods: ['GET'])]
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

        return $this->render('poste_two/prix.html.twig', [
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

    #[Route('/poste/two/error', name: 'app_poste_two_error', methods: ['GET'])]
    public function error(): Response
    {
        return $this->render('poste_two/error.html.twig');
    }

    #[Route('/{id}', name: 'app_poste_two_show', methods: ['GET'])]
    public function show(PosteTwo $posteTwo): Response
    {
        return $this->render('poste_two/show.html.twig', [
            'poste_two' => $posteTwo,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_approve_reservation_two')]
    public function approveReservation(PosteTwo $posteTwo, EntityManagerInterface $entityManager): Response
    {
        $posteTwo->setApprouved(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/{id}/edit', name: 'app_poste_two_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PosteTwo $posteTwo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PosteTwoType::class, $posteTwo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_poste_two_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('poste_two/edit.html.twig', [
            'poste_two' => $posteTwo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_poste_two_delete', methods: ['POST'])]
    public function delete(Request $request, PosteTwo $posteTwo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$posteTwo->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($posteTwo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }

#[Route('/poste/{id}/cancel', name: 'app_poste_one_cancel', methods: ['DELETE'])]
public function cancel(int $id, EntityManagerInterface $entityManager, PosteTwoRepository $posteTwoRepository): Response
{
    $posteTwo = $posteTwoRepository->find($id);

    if ($posteTwo !== null) {
        $entityManager->remove($posteTwo);
        $entityManager->flush();
    }

    return new JsonResponse(['status' => 'cancelled'], Response::HTTP_OK);
}
}
