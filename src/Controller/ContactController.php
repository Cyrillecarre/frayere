<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;



class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {

        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $adress = $data['email'];
            $message = $data['message'];
            
            $email = (new Email())
            ->from($adress)
            ->to('cyrille.carre@gmail.com')
            ->text($message);

            $mailer->send($email);
            return $this->redirectToRoute('app_validation');
        }

        return $this->render('contact/index.html.twig', [
            'controller_name' => 'ContactController',
            'formulaire' => $form,
        ]);
    }

    #[Route('/contact', name: 'app_validation')]
    public function emailEnvoye(): response
    {
        return $this->render('contact/validation.html.twig');
    }
}