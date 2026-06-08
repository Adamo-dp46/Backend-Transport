<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\CourrierStatus;
use App\Entity\Courrier;
use App\Entity\Detailcourrier;
use App\Entity\Dto\CourrierInput;
use App\Entity\User;
use App\Entity\Voyage;
use App\Repository\CourrierRepository;
use App\Repository\GareRepository;
use App\Repository\TarifcourrierRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourrierProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private GareRepository $gareRepository,
        private VoyageRepository $voyageRepository,
        private TarifcourrierRepository $tarifcourrierRepository,
        private CourrierRepository $courrierRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var CourrierInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $identreprise = $user->getEntreprise()->getId();

        if($operation instanceof Post) {
            return $this->handlePost($data, $user->getId(), $identreprise, $operation, $uriVariables, $context);
        }

        if($operation instanceof Patch) {
            return $this->handlePatch($data, $user->getId(), $identreprise, $operation, $uriVariables, $context);
        }
    }

    private function handlePost(CourrierInput $data, int $userId, int $identreprise, $operation, $uriVariables, $context): Courrier
    {
        $gareDepart = $this->gareRepository->findOneBy([
            'id' => $data->gareDepart,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$gareDepart) {
            throw new NotFoundHttpException('Gare de départ invalide');
        }

        $gareArrivee = $this->gareRepository->findOneBy([
            'id' => $data->gareArrivee,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$gareArrivee) {
            throw new NotFoundHttpException('Gare d\'arrivée invalide');
        }

        if($data->gareDepart === $data->gareArrivee) {
            throw new BadRequestHttpException('La gare de départ et la gare d\'arrivée doivent être différentes');
        }

        $voyage = null;
        if($data->voyage !== null) {
            $voyage = $this->voyageRepository->findOneBy([
                'id' => $data->voyage,
                'identreprise' => $identreprise,
                'deletedAt' => null
            ]);
            if(!$voyage) {
                throw new NotFoundHttpException('Voyage invalide');
            }
            // -- On pourrait faire la synchronisation statut auto..
        }

        $courrier = new Courrier();
        $courrier
            ->setIdentreprise($identreprise)
            ->setCreatedBy($userId)
            ->setNomexpediteur($data->nomexpediteur)
            ->setContactexpediteur($data->contactexpediteur)
            ->setNomdestinataire($data->nomdestinataire)
            ->setContactdestinataire($data->contactdestinataire)
            ->setGareDepart($gareDepart)
            ->setGareArrivee($gareArrivee)
            ->setVoyage($voyage)
            ->setFraissuivi($data->fraissuivi !== null ? (int)$data->fraissuivi : null)
            ->setStatut($this->resoudreStatut($voyage))
            ->setMontant(0)
            ->setCodecourrier($this->generateCode($identreprise))

            ->setModepaiement($data->modepaiement)
            ->setEtatpaiement($data->modepaiement === 'RECEPTION' ? 'EN_ATTENTE_PAIEMENT' : 'PAYE')
            ->setDatepaiement($data->modepaiement === 'ENVOI' ? new \DateTimeImmutable(): null)
        ;
        $this->em->persist($courrier);
        $this->em->flush(); /*
            - Vu qu'on a besoin pour avoir l'id avant de traiter les détails
        */
        $this->handleDetails($courrier, $data->details, $identreprise, $userId);

        return $this->processor->process($courrier, $operation, $uriVariables, $context);
    }

    private function handlePatch(CourrierInput $data, int $userId, int $identreprise, $operation, $uriVariables, $context): Courrier
    {
        $courrier = $this->courrierRepository->findOneBy([
            'id' => $uriVariables['id'],
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$courrier) {
            throw new NotFoundHttpException('Courrier invalide');
        }
        $gareDepart = $this->gareRepository->findOneBy([
            'id' => $data->gareDepart,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$gareDepart) {
            throw new NotFoundHttpException('Gare de départ invalide');
        }
        $gareArrivee = $this->gareRepository->findOneBy([
            'id' => $data->gareArrivee,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$gareArrivee) {
            throw new NotFoundHttpException('Gare d\'arrivée invalide');
        }
        if($data->gareDepart === $data->gareArrivee) {
            throw new BadRequestHttpException('Les gares de départ et d\'arrivée doivent être différentes');
        }
        $voyage = null;
        if($data->voyage !== null) {
            $voyage = $this->voyageRepository->findOneBy([
                'id' => $data->voyage,
                'identreprise' => $identreprise,
                'deletedAt' => null
            ]);
            if (!$voyage) throw new NotFoundHttpException('Voyage invalide');
        }

        if($data->modepaiement !== $courrier->getModepaiement() &&
            in_array($courrier->getStatut(), [
                CourrierStatus::STATUT_EN_TRANSIT->value,
                CourrierStatus::STATUT_RECEPTIONNE->value,
                CourrierStatus::STATUT_LIVRE->value,
            ])
        ) { /*
            - On bloque la odification du mode de paiement si le courrier est déjà 'EN_TRANSIT'
        */
            throw new BadRequestHttpException(
                'Le mode de paiement ne peut plus être modifié une fois le courrier en transit'
            );
        }

        $courrier
            ->setUpdatedBy($userId)
            ->setNomexpediteur($data->nomexpediteur)
            ->setContactexpediteur($data->contactexpediteur)
            ->setNomdestinataire($data->nomdestinataire)
            ->setContactdestinataire($data->contactdestinataire)
            ->setGareDepart($gareDepart)
            ->setGareArrivee($gareArrivee)
            ->setVoyage($voyage)
            ->setFraissuivi($data->fraissuivi !== null ? (int)$data->fraissuivi : null)
            ->setStatut($this->resoudreStatut($voyage))

            ->setModepaiement($data->modepaiement)
            ->setEtatpaiement($data->modepaiement === 'RECEPTION' ? 'EN_ATTENTE_PAIEMENT' : 'PAYE')
            ->setDatepaiement($data->modepaiement === 'ENVOI' ? new \DateTimeImmutable() : null)
        ;

        if (!empty($data->details)) {
            // ✅ 1. Valider tous les détails AVANT de toucher à la base
            $this->validerDetails($data->details, $identreprise);

            // ✅ 2. Seulement si tout est valide, on supprime et recrée
            foreach ($courrier->getDetailcourriers() as $detail) {
                $this->em->remove($detail);
            }
            $this->em->flush();

            $this->handleDetails($courrier, $data->details, $identreprise, $userId);
        }

        return $this->processor->process($courrier, $operation, $uriVariables, $context);
    }

    private function handleDetails(Courrier $courrier, array $details, int $identreprise, int $userId): void
    {
        $montantTotal = 0;
        foreach($details as $detailInput) {
            $valeur = (int)$detailInput['valeur'];
            if($valeur <= 0) {
                throw new BadRequestHttpException('La valeur d\'un colis doit être supérieure à 0');
            }

            $tarif = $this->tarifcourrierRepository->findTarifForValeur($valeur, $identreprise);
            if(!$tarif) {
                throw new BadRequestHttpException('Aucun tarif trouvé pour une valeur de ' . $valeur . '. Vérifiez la grille tarifaire');
            }

            $detail = new Detailcourrier();
            $detail
                ->setCourrier($courrier)
                ->setNature($detailInput['nature'])
                ->setDesignation($detailInput['designation'])
                ->setEmballage($detailInput['emballage'] ?? null)
                ->setType($detailInput['type'])
                ->setPoids(isset($detailInput['poids']) ? (int)$detailInput['poids'] : null)
                ->setValeur((int)$valeur)
                ->setMontant($tarif->getMontanttaxe())
                ->setTarifcourrier($tarif)
            ;
            $this->em->persist($detail);
            $montantTotal += (int)$tarif->getMontanttaxe();
        }

        $courrier->setMontant((int)$montantTotal);
    }

    private function generateCode(int $identreprise): string
    {
        $count = $this->courrierRepository->count([
            'identreprise' => $identreprise
        ]);
        return 'CRR-' . date('Y') . '-' . ($count + 1);
    }

    private function resoudreStatut(?Voyage $voyage): string
    {
        if($voyage === null) {
            return CourrierStatus::STATUT_EN_ATTENTE->value;
        }
        if($voyage->getDatefin() !== null) {
            return CourrierStatus::STATUT_RECEPTIONNE->value; /*
                - Le voyage clôturé alors les colis sont arrivés à la gare d'arrivée
            */
        }
        if($voyage->getDateDebut() !== null) {
            return CourrierStatus::STATUT_EN_TRANSIT->value;
        }
        return CourrierStatus::STATUT_EN_ATTENTE->value; /*
            - Une synchronisation statut auto selon voyage sauf si statut explicitement fourni
        */
    }

    private function validerDetails(array $details, int $identreprise): void
    {
        foreach($details as $detailInput) {
            $valeur = (int) $detailInput['valeur'];

            if ($valeur <= 0) {
                throw new BadRequestHttpException('La valeur d\'un colis doit être supérieure à 0');
            }

            $tarif = $this->tarifcourrierRepository->findTarifForValeur($valeur, $identreprise);
            if (!$tarif) {
                throw new BadRequestHttpException(
                    'Aucun tarif trouvé pour une valeur de ' . $valeur . '. Vérifiez la grille tarifaire.'
                );
            }
        }
    }
}
