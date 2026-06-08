<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Trait\PeriodeTrait;
use App\Entity\Output\Trajet\TrajetPerformanceDto;
use App\Entity\Output\Trajet\TrajetStatistiqueOutput;
use App\Entity\User;
use App\Repository\TrajetRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class TrajetStatsProvider implements ProviderInterface
{
    use PeriodeTrait;

    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private TrajetRepository $trajetRepository
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

        $performances = array_map(
            fn($row) => new TrajetPerformanceDto(
                id: $row['id'],
                provenance: $row['provenance'],
                destination: $row['destination'],
                codetrajet: $row['codetrajet'],
                nbvoyages: (int)$row['nbvoyages'],
                nbtickets: (int)$row['nbtickets'],
                recette: round((float)$row['recette'], 2),
            ),
            $this->trajetRepository->findAllAvecStats($dateDebut, $dateFin, $identreprise)
        );

        return new TrajetStatistiqueOutput(
            totalTrajets: $this->trajetRepository->countTotal($identreprise),
            performances: $performances,
        );
    }
}
