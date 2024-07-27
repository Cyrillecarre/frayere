<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PosteOne;
use App\Entity\PosteTwo;
use App\Entity\PosteThree;
use App\Entity\PosteFour;
use Psr\Log\LoggerInterface;



class PaymentController extends AbstractController
{
    private $urlGenerator;
    private $mailer;
    private $entityManager;
    private $logger;


    public function __construct(UrlGeneratorInterface $urlGenerator, MailerInterface $mailer, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->urlGenerator = $urlGenerator;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/create-checkout-session', name: 'app_payment_create', methods: ['POST'])]
    public function createCheckoutSession(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $this->logger->info('Data received: ' . print_r($data, true));

    
            if (!isset($data['totalPrice']) || !isset($data['isDeposit'])) {
                throw new \Exception("Données requises à l'entree manquantes.");
            }
    
            $totalPrice = (float) $data['totalPrice'];
            $isDeposit = (bool) $data['isDeposit'];
    
            if ($isDeposit) {
                $depositAmount = $totalPrice * 0.30;
                $amountToCharge = ceil($depositAmount / 10) * 10; // Arrondir à la dizaine supérieure
            } else {
                $amountToCharge = $totalPrice;
            }
    
            $amountToChargeCents = $amountToCharge * 100;
    
            Stripe::setApiKey($this->getParameter('stripe_secret_key'));
    
            $stripeSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $isDeposit ? 'Acompte de réservation' : 'Paiement de réservation',
                        ],
                        'unit_amount' => $amountToChargeCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('app_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
    
    
            return new JsonResponse(['id' => $stripeSession->id]);
    
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/payment-success', name: 'app_payment_success')]
    public function paymentSuccess(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
    
            return $this->render('payment/success.html.twig', [
                'stripe_public_key' => $this->getParameter('stripe_public_key'),
            ]);
    }

    #[Route('/payment-cancel', name: 'app_payment_cancel')]
    public function paymentCancel(): Response
    {
        return $this->render('payment/cancel.html.twig', [
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
        ]);
    }
}
