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
use Symfony\Component\Mime\Email;

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

    #[Route('/new', name: 'app_poste_two_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, PosteTwoRepository $posteTwoRepository): Response
    {
        $posteTwo = new PosteTwo();
        $form = $this->createForm(PosteTwoType::class, $posteTwo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $posteTwo->getStart();
            $end = $posteTwo->getEnd();
            $overlappingEvents = $posteTwoRepository->findOverlappingEvents($start, $end);
            
            if (count($overlappingEvents) > 0) {
                return $this->redirectToRoute('app_poste_two_error');
            } else {
                $entityManager->persist($posteTwo);
                $entityManager->flush();

                $email = (new Email())
                    ->from('la.frayere@la-frayere.fr')
                    ->to('la.frayere@la-frayere.fr')
                    ->subject('Nouvelle réservation au poste 2')
                    ->html('<p>Une nouvelle réservation au poste 2</p>');

                $mailer->send($email);

                return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('poste_two/new.html.twig', [
            'poste_two' => $posteTwo,
            'form' => $form,
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
}
