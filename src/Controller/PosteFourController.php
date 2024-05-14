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

    #[Route('/new', name: 'app_poste_four_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $posteFour = new PosteFour();
        $form = $this->createForm(PosteFourType::class, $posteFour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($posteFour);
            $entityManager->flush();

            $email = (new Email())
            ->from('la.frayere@la-frayere.fr')
            ->to('la.frayere@la-frayere.fr')
            ->subject('Nouvelle réservation au poste 4')
            ->html('<p>Une nouvelle réservation au poste 4</p>');

            $mailer->send($email);

            return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('poste_four/new.html.twig', [
            'poste_four' => $posteFour,
            'form' => $form,
        ]);
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
