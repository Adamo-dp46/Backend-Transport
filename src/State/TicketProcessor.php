<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\TicketStatus;
use App\Entity\Ligne;
use App\Entity\Siege;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Voyage;
use App\Repository\TarifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TicketProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private TarifRepository $tarifRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Ticket $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $data
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($user->getId());

        $voyage = $data->getVoyage();
        /*
            return $this->em->wrapInTransaction(function () use (...) {
                $this->em->lock($voyage, LockMode::PESSIMISTIC_WRITE); -- À activer pour sécuriser le test de chevauchement en concurrence
            });
        */
        if ($voyage->getDatefin() !== null) {
            throw new BadRequestHttpException('Ce voyage est clôturé, la vente de tickets est impossible');
        }

        if (!$voyage->getCar()) {
            throw new BadRequestHttpException('Aucun véhicule affecté à ce voyage');
        }

        $ligne = $voyage->getLigne();
        if (!$ligne) {
            throw new BadRequestHttpException('Ce voyage n\'est pas rattaché à une ligne (lancez le backfill ou créez le voyage sur une ligne)');
        }

        // 1. Siège
        $siege = $data->getSiege();
        if (!$siege) {
            throw new BadRequestHttpException('Siège obligatoire');
        }
        if ($siege->getCar()->getId() !== $voyage->getCar()->getId()) {
            throw new BadRequestHttpException('Ce siège n\'appartient pas au véhicule affecté au voyage');
        }

        // 2. Gares montée / descente (descente par défaut = terminus de la ligne)
        $garemontee = $data->getGare();
        if (!$garemontee) {
            throw new BadRequestHttpException('La gare d\'embarquement (montée) est obligatoire');
        }
        $garedescente = $data->getGaredescente() ?? $ligne->getGareterminus();
        $data->setGaredescente($garedescente);

        // 3. Ordres des arrêts de la ligne
        $ordreParGare = $this->ordreParGare($ligne);
        $monteeId = $garemontee->getId();
        $descenteId = $garedescente->getId();

        // Sécurité : un agent rattaché à une gare ne peut vendre qu'au départ de SA gare
        $userGare = $user->getGare();
        if ($userGare !== null && $monteeId !== $userGare->getId()) {
            throw new BadRequestHttpException('Vous ne pouvez vendre que des tickets au départ de votre gare (' . $userGare->getLibelle() . ')');
        }

        if (!isset($ordreParGare[$monteeId]) || !isset($ordreParGare[$descenteId])) {
            throw new BadRequestHttpException('La gare de montée ou de descente n\'est pas un arrêt de la ligne du voyage');
        }
        $ordreMontee = $ordreParGare[$monteeId];
        $ordreDescente = $ordreParGare[$descenteId];
        if ($ordreMontee >= $ordreDescente) {
            throw new BadRequestHttpException('La gare de descente doit être située après la gare de montée sur la ligne');
        }

        // 4. Prix depuis la GRILLE GLOBALE de l'entreprise (couple de gares, indépendant de la ligne)
        $tarif = $this->tarifRepository->findMontant($monteeId, $descenteId, $entrepriseId);
        if (!$tarif) {
            throw new BadRequestHttpException('Aucun tarif défini pour ce trajet (' . $garemontee->getLibelle() . ' → ' . $garedescente->getLibelle() . ') dans la grille tarifaire');
        }

        // 5. Capacité par segment : le siège ne doit chevaucher aucun ticket actif du voyage
        $this->assertSiegeLibre($data, $voyage, $siege, $entrepriseId, $ordreParGare, $ligne, $ordreMontee, $ordreDescente);

        // 6. Remise éventuelle + bénéficiaire
        $tarifMontant = $tarif->getMontant();
        $remise = $this->resoudreRemise($data, $tarifMontant);
        if ($remise > 0 && $data->getBeneficiaire() === null) {
            throw new BadRequestHttpException('Un bénéficiaire est obligatoire lorsqu\'une remise est appliquée');
            /*
                - Variante « bénéficiaire facultatif » : décommenter pour autoriser une remise
                  sans bénéficiaire (et supprimer le throw ci-dessus).
            */
        }
        if ($remise <= 0) {
            $data->setBeneficiaire(null); // pas de remise → on ne rattache pas de bénéficiaire
        }
        $data->setRemise($remise);

        // 7. Code + prix NET (tarif - remise) : toutes les recettes (SUM(prix)) restent justes
        $codeticket = $voyage->getCodevoyage() . '-' . $this->generateCode($entrepriseId, $voyage->getId());
        $data
            ->setCodeticket($codeticket)
            ->setPrix($tarifMontant - $remise);

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * Calcule le montant de la remise (FCFA) à partir des entrées transitoires du ticket
     * (remisetype + remisevaleur) et du tarif. Valide les bornes.
     */
    private function resoudreRemise(Ticket $data, int $tarif): int
    {
        $type = $data->getRemisetype();
        $valeur = $data->getRemisevaleur();
        if ($valeur === null || $valeur <= 0 || $type === null) {
            return 0;
        }
        $remise = match ($type) {
            'POURCENTAGE' => (int) round($tarif * min($valeur, 100) / 100),
            'MONTANT' => $valeur,
            default => throw new BadRequestHttpException('Type de remise invalide'),
        };
        if ($remise < 0) {
            $remise = 0;
        }
        if ($remise > $tarif) {
            throw new BadRequestHttpException('La remise ne peut pas dépasser le prix du billet (' . $tarif . ' FCFA)');
        }
        return $remise;
    }

    /**
     * @return array<int, int> map gareId => ordre
     */
    private function ordreParGare(Ligne $ligne): array
    {
        $map = [];
        foreach ($ligne->getArrets() as $arret) {
            $map[$arret->getGare()->getId()] = $arret->getOrdre();
        }
        return $map;
    }

    /**
     * Vérifie que le siège est disponible pour qui embarque à ordreMontee, avec PRIORITÉ À LA GARE AMONT :
     * on bloque uniquement si un passager existant est déjà assis à ce point (embarqué avant/à ordreMontee
     * et descend après). Un ticket vendu par une gare en aval (montée > ordreMontee) ne bloque pas — la gare
     * amont peut réutiliser le siège (le passager aval en conflit est réaccommodé par sa gare).
     */
    private function assertSiegeLibre(
        Ticket $data,
        Voyage $voyage,
        Siege $siege,
        int $entrepriseId,
        array $ordreParGare,
        Ligne $ligne,
        int $ordreMontee,
        int $ordreDescente
    ): void
    {
        $ordreTerminus = $ordreParGare[$ligne->getGareterminus()->getId()] ?? PHP_INT_MAX;

        $existants = $this->em->getRepository(Ticket::class)->findBy([
            'voyage' => $voyage,
            'siege' => $siege,
            'identreprise' => $entrepriseId,
            'statut' => TicketStatus::STATUT_VALIDE->value, // un billet reporté/annulé ne bloque plus le siège
            'deletedAt' => null,
        ]);

        foreach ($existants as $ticket) {
            if ($data->getId() !== null && $ticket->getId() === $data->getId()) {
                continue; // on s'ignore soi-même (cas d'une éventuelle modification)
            }
            $tm = $ordreParGare[$ticket->getGare()?->getId()] ?? null;
            $td = $ticket->getGaredescente()
                ? ($ordreParGare[$ticket->getGaredescente()->getId()] ?? null)
                : $ordreTerminus; // anciens tickets sans descente = jusqu'au terminus
            if ($tm === null || $td === null) {
                continue;
            }
            // Priorité à la gare amont : on bloque seulement si un passager est DÉJÀ assis au moment où le
            // nouveau client embarque (embarqué avant/à $ordreMontee ET descend après). Une vente d'une gare
            // en aval (tm > ordreMontee) ne bloque PAS la gare amont — elle reste prioritaire sur le siège.
            if ($tm <= $ordreMontee && $td > $ordreMontee) {
                throw new BadRequestHttpException('Ce siège est déjà occupé sur ce tronçon du voyage');
            }
        }
    }

    private function generateCode(int $entrepriseId, int $voyageId): string
    {
        $count = $this->em->getRepository(Ticket::class)->count([
            'identreprise' => $entrepriseId,
            'deletedAt' => null,
            'voyage' => $voyageId,
        ]);

        return 'TCK-' . date('Y') . '-' . ($count + 1);
    }
}
