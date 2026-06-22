<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Enum\TicketStatus;
use App\Repository\SiegeRepository;
use App\Repository\TicketRepository;
use App\Repository\VoyageRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class SiegeStateProvider implements ProviderInterface
{
    public function __construct(
        private SiegeRepository $siegeRepository,
        private TicketRepository $ticketRepository,
        private VoyageRepository $voyageRepository,
        private RequestStack $requestStack
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        $carParam = $request->query->get('car');
        $voyageId = $request->query->get('voyage');
        $monteeParam = $request->query->get('montee');     // gare de montée (id ou iri)
        $descenteParam = $request->query->get('descente');  // gare de descente (id ou iri)

        if (!$carParam) {
            return [];
        }
        $carId = $this->extractId($carParam);
        $sieges = $this->siegeRepository->findBy(['car' => $carId]);

        if (!$voyageId) {
            foreach ($sieges as $siege) {
                $siege->setStatut('LIBRE');
            }
            return $sieges;
        }

        $voyage = $this->voyageRepository->find($this->extractId($voyageId));
        // Seuls les billets VALIDE occupent un siège : un billet reporté/annulé est libéré
        $tickets = $this->ticketRepository->findBy([
            'voyage' => $this->extractId($voyageId),
            'statut' => TicketStatus::STATUT_VALIDE->value,
            'deletedAt' => null,
        ]);

        // Carte des ordres d'arrêt si la ligne est disponible → permet le calcul par tronçon
        $ordreParGare = [];
        $ligne = $voyage?->getLigne();
        if ($ligne) {
            foreach ($ligne->getArrets() as $arret) {
                $ordreParGare[$arret->getGare()->getId()] = $arret->getOrdre();
            }
        }

        // Tronçon demandé (si fourni et cohérent avec la ligne) → mode « par segment »
        $monteeId = $monteeParam ? $this->extractId($monteeParam) : null;
        $descenteId = $descenteParam ? $this->extractId($descenteParam) : null;
        $parSegment = $ligne
            && $monteeId !== null && $descenteId !== null
            && isset($ordreParGare[$monteeId], $ordreParGare[$descenteId])
            && $ordreParGare[$monteeId] < $ordreParGare[$descenteId];

        $ordreTerminus = $ligne ? ($ordreParGare[$ligne->getGareterminus()->getId()] ?? PHP_INT_MAX) : PHP_INT_MAX;
        $ordreMontee = $parSegment ? $ordreParGare[$monteeId] : null;
        $ordreDescente = $parSegment ? $ordreParGare[$descenteId] : null;

        $siegesOccupes = [];
        foreach ($tickets as $ticket) {
            if (!$ticket->getSiege()) {
                continue;
            }
            $siegeId = $ticket->getSiege()->getId();

            if (!$parSegment) {
                // Mode legacy : un siège est occupé dès qu'un ticket actif le référence
                $siegesOccupes[$siegeId] = true;
                continue;
            }

            // Mode par tronçon : occupé seulement si le ticket chevauche [montée, descente)
            $tm = $ordreParGare[$ticket->getGare()?->getId()] ?? null;
            $td = $ticket->getGaredescente()
                ? ($ordreParGare[$ticket->getGaredescente()->getId()] ?? null)
                : $ordreTerminus;
            if ($tm === null || $td === null) {
                $siegesOccupes[$siegeId] = true; // sécurité : ticket hors ligne → on bloque
                continue;
            }
            // Priorité à la gare amont : le siège n'est occupé pour qui embarque à $ordreMontee que si un
            // passager y est DÉJÀ assis à ce moment (embarqué avant/à ce point ET descend après). Les ventes
            // des gares en aval (tm > ordreMontee) ne grisent pas le siège — la gare amont reste prioritaire.
            if ($tm <= $ordreMontee && $td > $ordreMontee) {
                $siegesOccupes[$siegeId] = true;
            }
        }

        foreach ($sieges as $siege) {
            $siege->setStatut(isset($siegesOccupes[$siege->getId()]) ? 'OCCUPE' : 'LIBRE');
        }

        return $sieges;
    }

    private function extractId(string $iriOrId): int
    {
        if (str_contains($iriOrId, '/')) {
            $parts = explode('/', trim($iriOrId, '/'));
            return (int) end($parts);
        }
        return (int) $iriOrId;
    }
}
