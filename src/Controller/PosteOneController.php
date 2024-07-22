<?php

namespace App\Controller;

use App\Entity\PosteOne;
use App\Form\PosteOneType;
use App\Repository\PosteOneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/poste/one')]
class PosteOneController extends AbstractController
{
    #[Route('/', name: 'app_poste_one_index', methods: ['GET'])]
    public function index(PosteOneRepository $posteOneRepository): Response
    {
        return $this->render('poste_one/index.html.twig', [
            'poste_ones' => $posteOneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_poste_one_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, PosteOneRepository $posteOneRepository): Response
    {
        $posteOne = new PosteOne();
        $form = $this->createForm(PosteOneType::class, $posteOne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $posteOne->getStart();
            $end = $posteOne->getEnd();

            $duration = ($end->getTimestamp() - $start->getTimestamp()) / (60 * 60 * 24);

            if ($duration < 1.7) {
                return $this->redirectToRoute('app_poste_one_error'); 
            }

            $overlappingEvents = $posteOneRepository->findOverlappingEvents($start, $end);
            
            if (count($overlappingEvents) > 0) {
                return $this->redirectToRoute('app_poste_one_error');
            } else {
                $entityManager->persist($posteOne);
                $entityManager->flush();

                $email = (new Email())
                    ->from('la.frayere@la-frayere.fr')
                    ->to('la.frayere@la-frayere.fr')
                    ->subject('Nouvelle réservation au poste 1')
                    ->html('<p>Une nouvelle réservation au poste 1</p>');

                $mailer->send($email);

                return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('poste_one/new.html.twig', [
            'poste_one' => $posteOne,
            'form' => $form,
        ]);
    }

    #[Route('/poste/one/error', name: 'app_poste_one_error', methods: ['GET'])]
    public function error(): Response
    {
        return $this->render('poste_one/error.html.twig');
    }

    #[Route('/{id}', name: 'app_poste_one_show', methods: ['GET'])]
    public function show(PosteOne $posteOne): Response
    {
        return $this->render('poste_one/show.html.twig', [
            'poste_one' => $posteOne,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_approve_reservation_one')]
    public function approveReservation(PosteOne $posteOne, EntityManagerInterface $entityManager): Response
    {
        $posteOne->setApprouved(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/{id}/edit', name: 'app_poste_one_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PosteOne $posteOne, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PosteOneType::class, $posteOne);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_poste_one_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('poste_one/edit.html.twig', [
            'poste_one' => $posteOne,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_poste_one_delete', methods: ['POST'])]
    public function delete(Request $request, PosteOne $posteOne, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$posteOne->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($posteOne);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin', [], Response::HTTP_SEE_OTHER);
    }
}

