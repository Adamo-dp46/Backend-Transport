<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Detailcourrier;
use App\Entity\User;
use App\Repository\TarifcourrierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DetailcourrierProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private TarifcourrierRepository $tarifcourrierRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Detailcourrier $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $identreprise = $user->getEntreprise()->getId();

        if($operation instanceof Post) {
            // $data->setIdentreprise($identreprise);
            $this->calculerMontant($data, $identreprise);
            $this->em->persist($data);
            $this->em->flush();
            $this->recalculerMontantCourrier($data);
        }

        if($operation instanceof Patch) {
            $original = $this->em->getUnitOfWork()->getOriginalEntityData($data);
            $valeurChange = $data->getValeur() !== ($original['valeur'] ?? null);

            if ($valeurChange) {
                $this->calculerMontant($data, $identreprise);
                $this->em->flush();
                $this->recalculerMontantCourrier($data);
            }
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function calculerMontant(Detailcourrier $data, int $identreprise): void
    {
        $valeur = (float)$data->getValeur();
        if($valeur <= 0) {
            throw new BadRequestHttpException('La valeur du colis doit être supérieure à 0');
        }
        $tarif = $this->tarifcourrierRepository->findTarifForValeur($valeur, $identreprise);
        if (!$tarif) {
            throw new BadRequestHttpException('Aucun tarif trouvé pour une valeur de ' . $valeur . '. Vérifiez la grille tarifaire.');
        }
        $data->setMontant($tarif->getMontanttaxe()); /*
            ->setTarifcourrier($tarif);
        */
    }

    private function recalculerMontantCourrier(Detailcourrier $data): void
    {
        $courrier = $data->getCourrier();
        if(!$courrier) {
            return;
        }
        $total = $this->em->getRepository(Detailcourrier::class)
            ->createQueryBuilder('dc')
            ->select('SUM(dc.montant)')
            ->where('dc.courrier = :courrier')
            ->setParameter('courrier', $courrier)
            ->getQuery()
            ->getSingleScalarResult()
        ;
        $courrier->setMontant((string) ($total ?? '0'));
        // $this->em->flush();
    }
}
