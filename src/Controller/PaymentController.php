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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
            error_log(print_r($data, true));
 
            if (!isset($data['totalPrice']) || !isset($data['isDeposit']) || !isset($data['posteId']) || !isset($data['posteType']) || !isset($data['start']) || !isset($data['end'])) {
                throw new \Exception("Données requises à l'entree manquantes.");
            }
    
            $totalPrice = (float) $data['totalPrice'];
            $isDeposit = (bool) $data['isDeposit'];
            $posteId = $data['posteId'];
            $posteType = $data['posteType'];
            $start = $data['start'];
            $end = $data['end'];
            $pellets = $data['pellets'];
            $graines = $data['graines'];


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
                'success_url' => $this->generateUrl('app_payment_success', [
                    'poste_id' => $posteId, 
                    'poste_type' => $posteType, 
                    'totalPrice' => $totalPrice, 
                    'is_deposit' => $amountToCharge, 
                    'start' => $start,
                    'end' => $end,
                    'pellets' => $pellets, 
                    'graines' => $graines
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('app_payment_cancel', [
                    'poste_id' => $posteId, 
                    'poste_type' => $posteType
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'metadata' => [
                    'poste_id' => $posteId,
                    'poste_type' => $posteType,
                    'totalPrice' => $totalPrice,
                    'is_deposit' => $amountToCharge,
                    'start' => $start,
                    'end' => $end,
                    'pellets' => $pellets,
                    'graines' => $graines,
                ],
            ]);
    
    
            return new JsonResponse(['id' => $stripeSession->id]);
    
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/payment-success', name: 'app_payment_success')]
    public function paymentSuccess(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $reservationDetails = $session->get('reservation_details');
        if (!$reservationDetails) {
            throw $this->createNotFoundException('Détails de la réservation non trouvés dans la session.');
        }

        $posteTitle = $reservationDetails['poste_title'];
        $totalPrice = $request->query->get('totalPrice');
        $isDeposit = $request->query->get('is_deposit');
        $posteType = $reservationDetails['poste_type'];
        $start = $reservationDetails['start'];
        $end = $reservationDetails['end'];
        $numberOfFishers = $reservationDetails['numberOfFishers'];
        $pellets = $reservationDetails['pellets'];
        $graines = $reservationDetails['graines'];
        $email = $reservationDetails['email'];
        $phoneNumber = $reservationDetails['phoneNumber'];

        $newPoste = null;
        switch ($posteType) {
            case 'un':
                $newPoste = new PosteOne();
                break;
            case 'deux':
                $newPoste = new PosteTwo();
                break;
            case 'trois':
                $newPoste = new PosteThree();
                break;
            case 'quatre':
                $newPoste = new PosteFour();
                break;
            default:
                throw new \Exception('Type de poste invalide.');
        }


        $newPoste->setTitle($posteTitle);
        $newPoste->setStart($start);
        $newPoste->setEnd($end);
        $newPoste->setemail($email);
        $newPoste->setPhoneNumber($phoneNumber);
        $newPoste->setApprouved(true);
            $this->entityManager->persist($newPoste);
            $this->entityManager->flush();

        $email = (new Email())
            ->from('la.frayere@la-frayere.fr')
            ->to('la.frayere@la-frayere.fr')
            ->subject('Confirmation de réservation')
            ->html($this->renderView('payment/mailSuccess.html.twig', [
                'posteType' => $posteType,
                'start' => $start,
                'end' => $end,
                'totalPrice' => $totalPrice,
                'is_deposit' => $isDeposit,
                'pellets' => $pellets,
                'graines' => $graines,
            ]));

        $mailer->send($email);


            return $this->render('payment/success.html.twig', [
                'stripe_public_key' => $this->getParameter('stripe_public_key'),
                'posteType' => $posteType,
                'start' => $start,
                'end' => $end,
                'totalPrice' => $totalPrice,
                'is_deposit' => $isDeposit,
                'pellets' => $pellets,
                'graines' => $graines,
            ]);
    }

    #[Route('/payment-cancel', name: 'app_payment_cancel')]
    public function paymentCancel(EntityManagerInterface $entityManager, Request $request, SessionInterface $session): Response
    {

        $reservationDetails = $session->get('reservation_details');
        if (!$reservationDetails) {
            throw $this->createNotFoundException('Détails de la réservation non trouvés dans la session.');
        }

        return $this->render('payment/cancel.html.twig', [
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
        ]);
    }
}
