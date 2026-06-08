<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\VoyageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VoyageTrajetProvider implements ProviderInterface
{
    public function __construct(
        private VoyageRepository $voyageRepository,
        private Security $security,
        private RequestStack $requestStack
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
        $trajetId = $uriVariables['id'] ?? null;
        if(!$trajetId) {
            throw new NotFoundHttpException('Trajet introuvable'); /*
                - Vu qu'on a utilisé 'Link' au niveau de 'Voyage'
            */
        }
        $request = $this->requestStack->getCurrentRequest();
        $page = max(1, (int)$request?->query->get('page', 1));

        $itemsPerPage = in_array(
            (int) $request?->query->get('itemsPerPage', 30),
            [10, 20, 30, 50]
        ) ? (int) $request->query->get('itemsPerPage', 30) : 30;

        $dateDebut = $request->query->get('debut');
        $dateFin = $request->query->get('fin');
        $debut = isset($dateDebut) ? new \DateTimeImmutable($dateDebut) : null;
        $fin = isset($dateFin) ? new \DateTimeImmutable($dateFin) : null;

        return $this->voyageRepository->findByTrajet(
            $trajetId,
            $identreprise,
            $page,
            $itemsPerPage,
            $debut,
            $fin
        );
    }
}
