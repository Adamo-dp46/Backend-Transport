<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Inventaire;
use App\Entity\Output\Inventaire\InventaireOutput;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class InventaireProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: CollectionProvider::class)]
        private readonly ProviderInterface $collectionProvider,
        #[Autowire(service: ItemProvider::class)]
        private readonly ProviderInterface $itemProvider,
        private readonly UserRepository $userRepository
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $isCollection = $operation instanceof GetCollection;
        $data = $isCollection
            ? $this->collectionProvider->provide($operation, $uriVariables, $context)
            : $this->itemProvider->provide($operation, $uriVariables, $context)
        ; /*
            - On conserve le pipeline de 'ApiPlatform' ce qui applqiue les filtres comme 'EntrepriseScopeExtension'..
        */
        if($isCollection) {
            $items = iterator_to_array($data); /*
                - Vu que '$data' est un 'Paginator Doctrine' on peut itérer sans le détruire
            */
            $userIds = array_unique(array_filter(array_map( /*
                    - On récupère tous les 'createdBy' en une seule requête, pas de N+1
                */
                fn(Inventaire $i) => $i->getCreatedBy(),
                $items
            )));
            $usersIndex = $this->userRepository->findInfosByIds($userIds);

            $mapped = array_map(
                fn(Inventaire $i) => $this->toOutput($i, $usersIndex),
                $items
            );

            $currentPage = 1;
            $itemsPerPage = count($mapped);
            $totalItems = count($mapped);

            if($data instanceof PartialPaginatorInterface) {
                $currentPage = $data->getCurrentPage();
                $itemsPerPage = $data->getItemsPerPage();
            }

            if($data instanceof PaginatorInterface) {
                $totalItems = $data->getTotalItems();
            }

            return new TraversablePaginator( /*
                - On réemballe dans un 'TraversablePaginator' pour conserver la pagination
            */
                new \ArrayIterator($mapped),
                $currentPage,
                $itemsPerPage,
                $totalItems
            );
        }

        if(!$data instanceof Inventaire) {
            return null;
        }

        $usersIndex = $this->userRepository->findInfosByIds(
            array_filter([$data->getCreatedBy()])
        );

        return $this->toOutput($data, $usersIndex);
    }

    private function toOutput(Inventaire $inventaire, array $usersIndex): InventaireOutput
    {
        $user = $inventaire->getCreatedBy()
            ? ($usersIndex[$inventaire->getCreatedBy()] ?? null)
            : null
        ;
        return new InventaireOutput(
            id: $inventaire->getId(),
            typemouvement: $inventaire->getTypemouvement(),
            referencetype: $inventaire->getReferenceType(),
            referenceid: $inventaire->getReferenceId(),
            quantite: $inventaire->getQuantite(),
            datemouvement: $inventaire->getDatemouvement()?->format('Y-m-d H:i') ?? '',
            createdAt: $inventaire->getCreatedAt()?->format('Y-m-d H:i') ?? '',
            pieceName: $inventaire->getPiece()?->getLibelle(),
            createdBy: $inventaire->getCreatedBy(),
            createdByNom: $user['nom'] ?? null,
            createdByPrenom: $user['prenom'] ?? null
        );
    }
}
