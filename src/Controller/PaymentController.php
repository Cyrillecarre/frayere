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
 
            if (!isset($data['totalPrice']) || !isset($data['isDeposit']) || !isset($data['posteId']) || !isset($data['posteType'])) {
                throw new \Exception("Données requises à l'entree manquantes.");
            }
    
            $totalPrice = (float) $data['totalPrice'];
            $isDeposit = (bool) $data['isDeposit'];
            $posteId = $data['posteId'];
            $posteType = $data['posteType'];
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
    public function paymentSuccess(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $posteId = $request->query->get('poste_id');
        $posteType = $request->query->get('poste_type');
        if (!$posteId || !$posteType) {
            throw $this->createNotFoundException('ID ou type de poste non spécifié.');
        }

        $totalPrice = $request->query->get('totalPrice');
        $isDeposit = $request->query->get('is_deposit');
        $pellets = $request->query->get('pellets');
        $graines = $request->query->get('graines');

        $poste = null;
        switch ($posteType) {
            case 'one':
                $poste = $entityManager->getRepository(PosteOne::class)->find($posteId);
                break;
            case 'two':
                $poste = $entityManager->getRepository(PosteTwo::class)->find($posteId);
                break;
            case 'three':
                $poste = $entityManager->getRepository(PosteThree::class)->find($posteId);
                break;
            case 'four':
                $poste = $entityManager->getRepository(PosteFour::class)->find($posteId);
                break;
            default:
                throw new \Exception('Type de poste invalide.');
        }

        $poste->setApprouved(true);
        $entityManager->persist($poste);
        $entityManager->flush();

        $email = (new Email())
            ->from('la.frayere@la-frayere.fr')
            ->to('cyrille.carre@gmail.com')
            ->subject('Confirmation de réservation')
            ->html($this->renderView('payment/mailSuccess.html.twig', [
                'posteType' => $posteType,
                'totalPrice' => $totalPrice,
                'is_deposit' => $isDeposit,
                'pellets' => $pellets,
                'graines' => $graines,
            ]));

        $mailer->send($email);


            return $this->render('payment/success.html.twig', [
                'stripe_public_key' => $this->getParameter('stripe_public_key'),
                'posteType' => $posteType,
                'totalPrice' => $totalPrice,
                'is_deposit' => $isDeposit,
                'pellets' => $pellets,
                'graines' => $graines,
            ]);
    }

    #[Route('/payment-cancel', name: 'app_payment_cancel')]
    public function paymentCancel(EntityManagerInterface $entityManager, Request $request, MailerInterface $mailer): Response
    {

        $posteId = $request->query->get('poste_id');
        $posteType = $request->query->get('poste_type');
        if (!$posteId || !$posteType) {
            throw $this->createNotFoundException('ID ou type de poste non spécifié.');
        }

        $this->logger->info('ID et type du poste:', ['poste_id' => $posteId, 'poste_type' => $posteType]);


        $totalPrice = $request->query->get('totalPrice');
        $isDeposit = $request->query->get('isDeposit');
        $numNights = $request->query->get('numNights');
        $numFishers = $request->query->get('numFishers');
        $pellets = $request->query->get('pellets');
        $graines = $request->query->get('graines');

        $poste = null;
        switch ($posteType) {
            case 'one':
                $poste = $entityManager->getRepository(PosteOne::class)->find($posteId);
                break;
            case 'two':
                $poste = $entityManager->getRepository(PosteTwo::class)->find($posteId);
                break;
            case 'three':
                $poste = $entityManager->getRepository(PosteThree::class)->find($posteId);
                break;
            case 'four':
                $poste = $entityManager->getRepository(PosteFour::class)->find($posteId);
                break;
            default:
                $this->logger->error('Type de poste invalide.', ['poste_type' => $posteType]);
                throw new \Exception('Type de poste invalide.');
        }

        if ($poste) {
            $this->logger->info('Poste trouvé:', ['poste' => $poste]);
        } else {
            $this->logger->error('Poste non trouvé pour l\'ID:', ['poste_id' => $posteId]);
            throw $this->createNotFoundException('Le poste spécifié n\'existe pas.');
        }

        if ($poste) {
            $this->logger->info('Poste trouvé:', ['poste' => $poste]);
        } else {
            throw $this->createNotFoundException('Le poste spécifié n\'existe pas.');
        }

        $entityManager->remove($poste);
        $entityManager->flush();

        return $this->render('payment/cancel.html.twig', [
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
            'posteName' => $poste->getTitle(),
            'totalPrice' => $totalPrice,
            'isDeposit' => $isDeposit,
            'numNights' => $numNights,
            'numFishers' => $numFishers,
            'pellets' => $pellets,
            'graines' => $graines,
        ]);
    }
}
