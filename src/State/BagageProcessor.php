<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\BagageStatus;
use App\Entity\Bagage;
use App\Entity\Dto\BagageInput;
use App\Entity\Gare;
use App\Entity\User;
use App\Entity\Voyage;
use App\Repository\BagageRepository;
use App\Repository\TarifbagageRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BagageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private VoyageRepository $voyageRepository,
        private TarifbagageRepository $tarifbagageRepository,
        private BagageRepository $bagageRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var BagageInput $data */

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

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function handlePost(
        BagageInput $data,
        int $userId,
        int $identreprise,
        $operation,
        $uriVariables,
        $context
    ): Bagage
    {
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

            if($voyage->getDatefin() !== null) {
                throw new BadRequestHttpException('Ce voyage est clôturé, enregistrement de bagage impossible');
            }
        }

        [$montant, $tarifbagage, $montantforce] = $this->resoudreMontant(
            $data->poids,
            $data->montant,
            $identreprise
        );

        [$garedepart, $garedescente] = $this->resoudreGares($data, $voyage);

        $bagage = new Bagage();
        $bagage
            ->setIdentreprise($identreprise)
            ->setCreatedBy($userId)
            ->setVoyage($voyage)
            ->setGaredepart($garedepart)
            ->setGaredescente($garedescente)
            ->setNomclient($data->nomclient)
            ->setContactclient($data->contactclient)
            ->setNature($data->nature)
            ->setType($data->type)
            ->setPoids((int)$data->poids)
            ->setMontant($montant)
            ->setMontantforce($montantforce)
            ->setTarifbagage($tarifbagage)
            ->setStatut($this->resoudreStatut($voyage))
            ->setCodebagage($this->generateCode($identreprise))
        ;

        return $this->processor->process($bagage, $operation, $uriVariables, $context);
    }

    private function handlePatch(
        BagageInput $data,
        int $userId,
        int $identreprise,
        $operation,
        $uriVariables,
        $context
    ): Bagage
    {
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

            if($voyage->getDatefin() !== null) {
                throw new BadRequestHttpException('Ce voyage est clôturé,  modification impossible');
            }
        }

        $bagage = $this->bagageRepository->findOneBy([
            'id' => $uriVariables['id'],
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);

        if(!$bagage) {
            throw new NotFoundHttpException('Bagage invalide');
        }

        if(in_array($bagage->getStatut(), [BagageStatus::STATUT_LIVRE->value, BagageStatus::STATUT_PERDU->value])) {
            throw new BadRequestHttpException('Ce bagage ne peut plus être modifié');
        }

        if($bagage->getStatut() !== BagageStatus::STATUT_ENREGISTRE->value) {
            throw new BadRequestHttpException('Seul un bagage enregistré peut être modifié. Statut actuel : ' . $bagage->getStatut());
        }
        /*
            if($bagage->getVoyage()->getDatefin() !== null) { -- Va être bloquant si on a clôturer le voyage avant de déclarer que le bagage est perdu
                throw new BadRequestHttpException('Ce voyage est clôturé, modification impossible');
            }
        */
        [$montant, $tarifbagage, $montantforce] = $this->resoudreMontant(
            $data->poids,
            $data->montant,
            $identreprise
        );

        [$garedepart, $garedescente] = $this->resoudreGares($data, $voyage);

        $bagage
            ->setUpdatedBy($userId)
            ->setNomclient($data->nomclient)
            ->setContactclient($data->contactclient)
            ->setNature($data->nature)
            ->setType($data->type)
            ->setPoids((int)$data->poids)
            ->setMontant($montant)
            ->setMontantforce($montantforce)
            ->setTarifbagage($tarifbagage)
            ->setVoyage($voyage)
            ->setGaredepart($garedepart)
            ->setGaredescente($garedescente)
            ->setStatut($this->resoudreStatut($voyage))
            ->setUpdatedAt(new \DateTimeImmutable())
        ;

        return $this->processor->process($bagage, $operation, $uriVariables, $context);
    }

    /**
     * Résout les gares d'origine et de descente du bagage.
     * - Sans voyage : aucune gare.
     * - Avec voyage : les deux gares sont des arrêts de la ligne, la descente après l'origine.
     *   L'origine est FORCÉE à la gare de l'agent s'il y est rattaché (sécurité : pas d'usurpation
     *   de provenance) ; sinon elle vaut la gare d'origine de la ligne par défaut.
     *
     * @return array{0: ?Gare, 1: ?Gare}
     */
    private function resoudreGares(BagageInput $data, ?Voyage $voyage): array
    {
        if ($voyage === null) {
            return [null, null];
        }

        $ligne = $voyage->getLigne();
        if (!$ligne) {
            throw new BadRequestHttpException('Le voyage sélectionné n\'est rattaché à aucune ligne');
        }

        $ordreParGare = [];
        $gareParId = [];
        foreach ($ligne->getArrets() as $arret) {
            $g = $arret->getGare();
            $ordreParGare[$g->getId()] = $arret->getOrdre();
            $gareParId[$g->getId()] = $g;
        }

        // Gare d'origine
        $userGare = $this->security->getUser()->getGare();
        if ($userGare !== null) {
            if (!isset($gareParId[$userGare->getId()])) {
                throw new BadRequestHttpException('Votre gare (' . $userGare->getLibelle() . ') n\'est pas desservie par ce voyage');
            }
            $garedepart = $gareParId[$userGare->getId()];
        } elseif ($data->garedepart !== null) {
            if (!isset($gareParId[$data->garedepart])) {
                throw new BadRequestHttpException('La gare de départ doit être un arrêt de la ligne du voyage');
            }
            $garedepart = $gareParId[$data->garedepart];
        } else {
            $garedepart = $ligne->getGareorigine();
        }

        // Gare de descente (par défaut : terminus)
        if ($data->garedescente !== null) {
            if (!isset($gareParId[$data->garedescente])) {
                throw new BadRequestHttpException('La gare de descente doit être un arrêt de la ligne du voyage');
            }
            $garedescente = $gareParId[$data->garedescente];
        } else {
            $garedescente = $ligne->getGareterminus();
        }

        // Sens du trajet : la descente doit être après l'origine
        $ordreDepart = $ordreParGare[$garedepart->getId()] ?? -1;
        $ordreDescente = $ordreParGare[$garedescente->getId()] ?? PHP_INT_MAX;
        if ($ordreDescente <= $ordreDepart) {
            throw new BadRequestHttpException('La gare de descente doit être située après la gare de départ');
        }

        return [$garedepart, $garedescente];
    }

    private function resoudreStatut(?Voyage $voyage): string
    {
        if($voyage === null) {
            return BagageStatus::STATUT_ENREGISTRE->value;
        }
        if($voyage->getDatefin() !== null) {
            return BagageStatus::STATUT_LIVRE->value;
        }
        return BagageStatus::STATUT_EMBARQUE->value;
    }

    /**
     * Permet de résoudre le montant final
     *  - Va chercher le tarif correspondant au poids
     *  - Si tarif trouvé et montant non fourni → montant du tarif
     *  - Si tarif trouvé et montant fourni différent → montant forcé et 'tarifbagage' conservé pour historique
     *  - Si aucun tarif trouvé et montant fourni → montant forcé
     *  - Si aucun tarif trouvé et montant non fourni → erreur
     */
    private function resoudreMontant(float $poids, ?int $montantFourni, int $identreprise): array
    {
        $tarifbagage = $this->tarifbagageRepository->findTarifForPoids($poids, $identreprise);

        if($tarifbagage !== null) {
            $montantCalcule = $tarifbagage->getMontant();
            if($montantFourni !== null && $montantFourni !== $montantCalcule) {
                return [
                    $montantFourni,
                    $tarifbagage,
                    true
                ]; /*
                    - L'agent force un montant différent alors on garde le tarif pour l'historique
                */
            }
            return [
                $montantCalcule,
                $tarifbagage,
                false
            ];
        }

        if($montantFourni === null) {
            throw new BadRequestHttpException('Aucun tarif trouvé pour ' . $poids . ' kg. Veuillez saisir le montant manuellement');
        }

        return [$montantFourni, null, true];
    }

    private function generateCode(int $identreprise): string
    {
        $count = $this->bagageRepository->count([
            'identreprise' => $identreprise,
            'deletedAt' => null /*
                - Peut être bloquant si on n'avait pas le validator 'UniquePerEntreprise'
            */
        ]);
        return 'BAG-' . date('Y') . '-' . ($count + 1);
    }
}