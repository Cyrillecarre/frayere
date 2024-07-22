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
use Symfony\Component\Mime\Email;

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

    #[Route('/new', name: 'app_poste_three_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, PosteThreeRepository $posteThreeRepository): Response
    {
        $posteThree = new PosteThree();
        $form = $this->createForm(PosteThreeType::class, $posteThree);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $posteThree->getStart();
            $end = $posteThree->getEnd();
            $overlappingEvents = $posteThreeRepository->findOverlappingEvents($start, $end);

            if (count($overlappingEvents) > 0) {
                return $this->redirectToRoute('app_poste_three_error');
            } else {
            $entityManager->persist($posteThree);
            $entityManager->flush();

            $email = (new Email())
            ->from('la.frayere@la-frayere.fr')
            ->to('la.frayere@la-frayere.fr')
            ->subject('Nouvelle réservation au poste 3')
            ->html('<p>Une nouvelle réservation au poste 3</p>');

            $mailer->send($email);

            return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
        }
    }

        return $this->render('poste_three/new.html.twig', [
            'poste_three' => $posteThree,
            'form' => $form,
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
