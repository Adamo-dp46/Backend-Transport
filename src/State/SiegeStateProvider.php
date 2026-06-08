<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\SiegeRepository;
use App\Repository\TicketRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class SiegeStateProvider implements ProviderInterface
{
    public function __construct(
        private SiegeRepository $siegeRepository,
        private TicketRepository $ticketRepository,
        private RequestStack $requestStack
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        $carParam = $request->query->get('car');
        $voyageId = $request->query->get('voyage');
        if(!$carParam) {
            return [];
        }
        $carId = $this->extractId($carParam); /*
            - On extrais l'id depuis l'iri '/api/cars/5'
        */
        $sieges = $this->siegeRepository->findBy(['car' => $carId]); // On peut vérifier le 'identreprise'
        $siegesOccupes = []; /*
            - On calcule les sièges occupés d'un voyage
        */
        if($voyageId) {
            $tickets = $this->ticketRepository->findBy(['voyage' => $voyageId]);
            foreach($tickets as $ticket) {
                if($ticket->getSiege()) {
                    $siegesOccupes[$ticket->getSiege()->getId()] = true;
                }
            }
        }

        foreach($sieges as $siege) {
            $statut = isset($siegesOccupes[$siege->getId()]) ? 'OCCUPE' : 'LIBRE'; /*
                - On injecte le statut sur chaque siège
            */
            $siege->setStatut($statut);
        }

        return $sieges;
    }

    private function extractId(string $iriOrId): int
    {
        if(str_contains($iriOrId, '/')) {
            $parts = explode('/', trim($iriOrId, '/'));
            return (int) end($parts);
        }
        return (int)$iriOrId;
    }
}
