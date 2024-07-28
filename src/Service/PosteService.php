<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\PosteOne;
use App\Entity\PosteTwo;
use App\Entity\PosteThree;
use App\Entity\PosteFour;

class PosteService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getPosteEntity(int $posteId)
    {
        switch ($posteId) {
            case 1:
                $poste = $this->entityManager->getRepository(PosteOne::class)->find($posteId);
                break;
            case 2:
                $poste = $this->entityManager->getRepository(PosteTwo::class)->find($posteId);
                break;
            case 3:
                $poste = $this->entityManager->getRepository(PosteThree::class)->find($posteId);
                break;
            case 4:
                $poste = $this->entityManager->getRepository(PosteFour::class)->find($posteId);
                break;
            default:
                throw new NotFoundHttpException('Le poste spécifié n\'existe pas.');
        }

        if (!$poste) {
            throw new NotFoundHttpException('L\'enregistrement n\'existe pas.');
        }

        return $poste;
    }
}