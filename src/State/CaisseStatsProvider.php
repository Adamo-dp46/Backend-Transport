<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Trait\PeriodeTrait;
use App\Entity\Output\Caisse\CaisseDetailVoyageDto;
use App\Entity\Output\Caisse\CaisseOutput;
use App\Entity\Output\Caisse\CaisseParAgentDto;
use App\Entity\Output\Caisse\CaisseParJourDto;
use App\Entity\User;
use App\Repository\BagageRepository;
use App\Repository\CourrierRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CaisseStatsProvider implements ProviderInterface
{
    use PeriodeTrait;

    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private TicketRepository $ticketRepository,
        private CourrierRepository $courrierRepository,
        private BagageRepository $bagageRepository,
        private UserRepository $userRepository
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();
        $identreprise = $user->getEntreprise()->getId();
        $request = $this->requestStack->getCurrentRequest();
        [$dateDebut, $dateFin] = $this->parsePeriode($request);

        $totalTickets = $this->ticketRepository->countTotal($dateDebut, $dateFin, $identreprise);
        $recetteTickets = $this->ticketRepository->recettesTotales($dateDebut, $dateFin, $identreprise);
        $recetteCourriers = $this->courrierRepository->recettesTotales($dateDebut, $dateFin, $identreprise);
        $recetteBagages   = $this->bagageRepository->recettesTotales($dateDebut, $dateFin, $identreprise);

        // ── Par agent ────────────────────────────────────────────
        $rawTickets   = $this->ticketRepository->detailParAgentEtVoyage($dateDebut, $dateFin, $identreprise);
        $rawCourriers = $this->courrierRepository->recettesParAgent($dateDebut, $dateFin, $identreprise);
        $rawBagages = $this->bagageRepository->recettesParAgent($dateDebut, $dateFin, $identreprise);

        $agentsMap = [];
        foreach ($rawTickets as $row) {
            $id = $row['agentid'];
            $agentsMap[$id]['agentId']   = $id;
            $agentsMap[$id]['nom']       = $row['nom'];
            $agentsMap[$id]['prenom']    = $row['prenom'];
            $agentsMap[$id]['nbtickets']       = ($agentsMap[$id]['nbtickets'] ?? 0) + (int)$row['nbtickets'];
            $agentsMap[$id]['recetteTickets']  = ($agentsMap[$id]['recetteTickets'] ?? 0) + (float)$row['recette'];
            $agentsMap[$id]['detail'][]  = new CaisseDetailVoyageDto(
                codevoyage:  $row['codevoyage'],
                provenance:  $row['provenance'],
                destination: $row['destination'],
                nbtickets:   (int)$row['nbtickets'],
                recette:     round((float)$row['recette'], 2),
            );
        }

        $couriersParAgent = [];
        foreach ($rawCourriers as $row) {
            $id = $row['agentid'];
            $couriersParAgent[$id]['nbcourriers'] = ($couriersParAgent[$id]['nbcourriers'] ?? 0) + (int)$row['nbcourriers'];
            $couriersParAgent[$id]['recetteCourriers'] = ($couriersParAgent[$id]['recetteCourriers'] ?? 0) + (float)$row['montant'];
        } /*
            - On regroupe proprement par 'agentid' + 'jour' avant de merger
        */
        foreach($couriersParAgent as $id => $vals) {
            $agentsMap[$id]['agentId'] = $agentsMap[$id]['agentId'] ?? $id;
            $agentsMap[$id]['nbcourriers'] = $vals['nbcourriers'];
            $agentsMap[$id]['recetteCourriers'] = $vals['recetteCourriers'];
        }

        $bagagesParAgent = [];
        foreach ($rawBagages as $row) {
            $id = $row['agentid'];
            $bagagesParAgent[$id]['nbbagages']      = ($bagagesParAgent[$id]['nbbagages'] ?? 0) + (int)$row['nbbagages'];
            $bagagesParAgent[$id]['recetteBagages'] = ($bagagesParAgent[$id]['recetteBagages'] ?? 0) + (float)$row['montant'];
        } /*
            - !!
        */
        foreach($bagagesParAgent as $id => $vals) {
            $agentsMap[$id]['agentId'] = $agentsMap[$id]['agentId'] ?? $id;
            $agentsMap[$id]['nbbagages'] = $vals['nbbagages'];
            $agentsMap[$id]['recetteBagages'] = $vals['recetteBagages'];
        }

        // Résoudre les noms des agents sans tickets (courriers/bagages seulement)
        $idsManquants = array_filter(array_keys($agentsMap), fn($id) => !isset($agentsMap[$id]['nom']));
        if (!empty($idsManquants)) {
            $usersIndex = $this->userRepository->findInfosByIds($idsManquants);
            foreach ($idsManquants as $id) {
                $agentsMap[$id]['nom']    = $usersIndex[$id]['nom'] ?? '—';
                $agentsMap[$id]['prenom'] = $usersIndex[$id]['prenom'] ?? '—';
            }
        }

        $parAgent = array_map(fn($a) => new CaisseParAgentDto(
            agentId:          $a['agentId'],
            nom:              $a['nom'],
            prenom:           $a['prenom'],
            nbtickets:        $a['nbtickets'] ?? 0,
            recetteTickets:   round($a['recetteTickets'] ?? 0, 2),
            nbcourriers:      $a['nbcourriers'] ?? 0,
            recetteCourriers: round($a['recetteCourriers'] ?? 0, 2),
            nbbagages:        $a['nbbagages'] ?? 0,
            recetteBagages:   round($a['recetteBagages'] ?? 0, 2),
            recetteTotale:    round(($a['recetteTickets'] ?? 0) + ($a['recetteCourriers'] ?? 0) + ($a['recetteBagages'] ?? 0), 2),
            detailParVoyage:  $a['detail'] ?? [],
        ), array_values($agentsMap));

        // ── Par jour ─────────────────────────────────────────────
        $rawJoursTickets   = $this->ticketRepository->detailParJourEtVoyage($dateDebut, $dateFin, $identreprise);
        $rawJoursCourriers = $this->courrierRepository->recettesParJourDetail($dateDebut, $dateFin, $identreprise);
        $rawJoursBagages   = $this->bagageRepository->recettesParJourDetail($dateDebut, $dateFin, $identreprise);

        $joursMap = [];

        foreach ($rawJoursTickets as $row) {
            $jour = $row['jour'];
            $joursMap[$jour]['nbtickets']      = ($joursMap[$jour]['nbtickets'] ?? 0) + (int)$row['nbtickets'];
            $joursMap[$jour]['recetteTickets'] = ($joursMap[$jour]['recetteTickets'] ?? 0) + (float)$row['recette'];
            $joursMap[$jour]['detail'][]       = new CaisseDetailVoyageDto(
                codevoyage:  $row['codevoyage'],
                provenance:  $row['provenance'],
                destination: $row['destination'],
                nbtickets:   (int)$row['nbtickets'],
                recette:     round((float)$row['recette'], 2),
            );
        }

        foreach ($rawJoursCourriers as $row) {
            $jour = $row['label'];
            $joursMap[$jour]['nbcourriers'] = ($joursMap[$jour]['nbcourriers'] ?? 0) + (int)$row['nbcourriers'];
            $joursMap[$jour]['recetteCourriers'] = ($joursMap[$jour]['recetteCourriers'] ?? 0) + (float)$row['montant'];
        }

        foreach ($rawJoursBagages as $row) {
            $jour = $row['label'];
            $joursMap[$jour]['nbbagages'] = ($joursMap[$jour]['nbbagages'] ?? 0) + (int)$row['nbbagages'];
            $joursMap[$jour]['recetteBagages'] = ($joursMap[$jour]['recetteBagages'] ?? 0) + (float)$row['montant'];
        }

        ksort($joursMap);

        $parJour = array_map(fn(string $jour, $j) => new CaisseParJourDto(
            jour: $jour,
            nbtickets: $j['nbtickets'] ?? 0,
            recetteTickets: round($j['recetteTickets'] ?? 0, 2),
            nbcourriers: $j['nbcourriers'] ?? 0,
            recetteCourriers: round($j['recetteCourriers'] ?? 0, 2),
            nbbagages: $j['nbbagages'] ?? 0,
            recetteBagages:   round($j['recetteBagages'] ?? 0, 2),
            recetteTotale:    round(($j['recetteTickets'] ?? 0) + ($j['recetteCourriers'] ?? 0) + ($j['recetteBagages'] ?? 0), 2),
            detailParVoyage:  $j['detail'] ?? [],
        ), array_keys($joursMap), array_values($joursMap));

        $totalCourriers = array_sum(array_column(array_values($couriersParAgent), 'nbcourriers'));
        $totalBagages = array_sum(array_column(array_values($bagagesParAgent), 'nbbagages'));

        return new CaisseOutput(
            totalTickets:     $totalTickets,
            recetteTickets:   $recetteTickets,
            totalCourriers:   $totalCourriers,
            recetteCourriers: $recetteCourriers,
            totalBagages:     $totalBagages,
            recetteBagages:   $recetteBagages,
            recetteTotale:    $recetteTickets + $recetteCourriers + $recetteBagages,
            parAgent:         $parAgent,
            parJour: $parJour
        );
    }
}
