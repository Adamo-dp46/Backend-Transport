<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\CourrierStatus;
use App\Domain\Enum\DetailcourrierStatus;
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
        // Voyage (optionnel)
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
        }

        // Les gares ne sont définies que si un voyage est choisi (et doivent être des arrêts de sa ligne)
        [$gareDepart, $gareArrivee] = $this->resoudreGares($data, $voyage, $identreprise);

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

        // Voyage (optionnel)
        $voyage = null;
        if($data->voyage !== null) {
            $voyage = $this->voyageRepository->findOneBy([
                'id' => $data->voyage,
                'identreprise' => $identreprise,
                'deletedAt' => null
            ]);
            if (!$voyage) throw new NotFoundHttpException('Voyage invalide');
        }

        // Les gares ne sont définies que si un voyage est choisi (et doivent être des arrêts de sa ligne)
        [$gareDepart, $gareArrivee] = $this->resoudreGares($data, $voyage, $identreprise);

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
            $this->reconcileDetails($courrier, $data->details, $identreprise, $userId); /*
                - Réconciliation par id : on conserve les colis existants (id + statut, ex. PERDU)
            */
        }

        return $this->processor->process($courrier, $operation, $uriVariables, $context);
    }

    /**
     * Réconcilie les colis d'un courrier lors d'une modification, PAR ID, plutôt que de
     * tout supprimer pour recréer :
     *  - colis existant (id reçu)  → mis à jour EN PLACE (son id ET son statut, ex. PERDU, sont conservés)
     *  - colis nouveau (sans id)   → créé
     *  - colis retiré (id absent)  → supprimé, SAUF s'il est déclaré perdu (on préserve la trace)
     * La taxe de chaque colis est recalculée depuis la grille (valeur → TarifCourrier).
     */
    private function reconcileDetails(Courrier $courrier, array $details, int $identreprise, int $userId): void
    {
        // Validation préalable : aucune mutation tant que tous les colis n'ont pas un tarif valide
        $this->validerDetails($details, $identreprise);

        /** @var array<int, Detailcourrier> $existants */
        $existants = [];
        foreach ($courrier->getDetailcourriers() as $detail) {
            $existants[$detail->getId()] = $detail;
        }

        $idsConserves = [];
        $montantTotal = 0;

        foreach ($details as $detailInput) {
            $valeur = (int) $detailInput['valeur'];
            $tarif = $this->tarifcourrierRepository->findTarifForValeur($valeur, $identreprise); // garanti non-null (validerDetails)

            $id = isset($detailInput['id']) ? (int) $detailInput['id'] : null;

            if ($id !== null && isset($existants[$id])) {
                $detail = $existants[$id]; // mise à jour EN PLACE : id + statut conservés
                $idsConserves[$id] = true;
            } else {
                $detail = new Detailcourrier();
                $detail->setCourrier($courrier);
                $this->em->persist($detail);
            }

            $detail
                ->setNature($detailInput['nature'])
                ->setDesignation($detailInput['designation'] ?? null)
                ->setEmballage($detailInput['emballage'] ?? null)
                ->setType($detailInput['type'])
                ->setPoids(isset($detailInput['poids']) ? (int) $detailInput['poids'] : null)
                ->setValeur($valeur)
                ->setMontant($tarif->getMontanttaxe())
                ->setTarifcourrier($tarif);

            $montantTotal += (int) $tarif->getMontanttaxe();
        }

        foreach ($existants as $id => $detail) { /*
            - Colis présents avant mais absents maintenant
        */
            if (!isset($idsConserves[$id])) {
                if ($detail->getStatut() === DetailcourrierStatus::STATUT_PERDU->value) {
                    throw new BadRequestHttpException(sprintf(
                        'Impossible de retirer le colis "%s" : il est déclaré perdu.',
                        $detail->getDesignation() ?: $detail->getNature()
                    ));
                }
                $this->em->remove($detail);
            }
        }

        $courrier->setMontant((int) $montantTotal);
    }

    /**
     * Résout les gares de départ/arrivée d'un courrier.
     * - Sans voyage : aucune gare (le colis est simplement enregistré, EN_ATTENTE d'affectation).
     * - Avec voyage : les deux gares sont obligatoires, valides, distinctes et sur la ligne du voyage.
     *
     * @return array{0: ?Gare, 1: ?Gare}
     */
    private function resoudreGares(CourrierInput $data, ?Voyage $voyage, int $identreprise): array
    {
        if ($voyage === null) {
            return [null, null];
        }

        if ($data->gareDepart === null || $data->gareArrivee === null) {
            throw new BadRequestHttpException('Sélectionnez la gare de départ et la gare d\'arrivée');
        }
        if ($data->gareDepart === $data->gareArrivee) {
            throw new BadRequestHttpException('La gare de départ et la gare d\'arrivée doivent être différentes');
        }

        $gareDepart = $this->gareRepository->findOneBy([
            'id' => $data->gareDepart,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if (!$gareDepart) {
            throw new NotFoundHttpException('Gare de départ invalide');
        }

        $gareArrivee = $this->gareRepository->findOneBy([
            'id' => $data->gareArrivee,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if (!$gareArrivee) {
            throw new NotFoundHttpException('Gare d\'arrivée invalide');
        }

        // Sécurité : un agent rattaché à une gare ne peut créer un courrier qu'au départ de SA gare
        $userGare = $this->security->getUser()->getGare();
        if ($userGare !== null && $gareDepart->getId() !== $userGare->getId()) {
            throw new BadRequestHttpException('Vous ne pouvez créer que des courriers au départ de votre gare (' . $userGare->getLibelle() . ')');
        }

        $this->assertGaresSurLigne($voyage, $data->gareDepart, $data->gareArrivee);

        return [$gareDepart, $gareArrivee];
    }

    /**
     * Cohérence avec la billetterie : un colis qui voyage sur un voyage donné ne peut
     * partir/arriver que dans des gares desservies par la ligne de ce voyage, et dans
     * le sens du trajet (arrivée après départ). Ne s'applique que si un voyage est fourni.
     */
    private function assertGaresSurLigne(Voyage $voyage, int $gareDepartId, int $gareArriveeId): void
    {
        $ligne = $voyage->getLigne();
        if (!$ligne) {
            throw new BadRequestHttpException('Le voyage sélectionné n\'est rattaché à aucune ligne');
        }

        $ordreParGare = [];
        foreach ($ligne->getArrets() as $arret) {
            $ordreParGare[$arret->getGare()->getId()] = $arret->getOrdre();
        }

        if (!isset($ordreParGare[$gareDepartId], $ordreParGare[$gareArriveeId])) {
            throw new BadRequestHttpException('La gare de départ et la gare d\'arrivée doivent être des arrêts de la ligne du voyage sélectionné');
        }

        if ($ordreParGare[$gareDepartId] >= $ordreParGare[$gareArriveeId]) {
            throw new BadRequestHttpException('La gare d\'arrivée doit être située après la gare de départ sur la ligne du voyage');
        }
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
