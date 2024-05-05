<?php

namespace App\Controller;

use App\Entity\PosteThree;
use App\Form\PosteThreeType;
use App\Repository\PosteThreeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $posteThree = new PosteThree();
        $form = $this->createForm(PosteThreeType::class, $posteThree);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($posteThree);
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('poste_three/new.html.twig', [
            'poste_three' => $posteThree,
            'form' => $form,
        ]);
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

        return $this->redirectToRoute('app_poste_three_edit', ['id' => $posteThree->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_poste_three_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PosteThree $posteThree, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PosteThreeType::class, $posteThree);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_poste_three_index', [], Response::HTTP_SEE_OTHER);
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
