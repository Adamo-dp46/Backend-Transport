<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Output\Bordereau\BordereauGareDto;
use App\Entity\Output\Bordereau\BordereauOutput;
use App\Entity\Output\Bordereau\BordereauPassagerDto;
use App\Entity\Output\Bordereau\BordereauVoyageDto;
use App\Entity\User;
use App\Repository\GareRepository;
use App\Repository\TicketRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BordereauProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly TicketRepository $ticketRepository,
        private readonly VoyageRepository $voyageRepository,
        private readonly GareRepository $gareRepository,
        private readonly RequestStack $requestStack
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

        $voyageId = $uriVariables['id'] ?? null;
        $gareId = (int)$this->requestStack->getCurrentRequest()?->query->get('gare');

        if (!$gareId) {
            throw new BadRequestHttpException('Le paramètre gare est obligatoire');
        }

        $voyage = $this->voyageRepository->findOneBy([
            'id' => $voyageId,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$voyage) {
            throw new NotFoundHttpException('Voyage introuvable');
        }

        $gare = $this->gareRepository->findOneBy([
            'id' => $gareId,
            'identreprise' => $identreprise,
            'deletedAt' => null
        ]);
        if(!$gare) {
            throw new NotFoundHttpException('Gare introuvable');
        }

        $stats = $this->ticketRepository->findBordereauStats($voyageId, (int)$gareId, $identreprise);
        $rawPassagers = $this->ticketRepository->findPassagers($voyageId, (int)$gareId, $identreprise);

        $passagers = array_map(
            fn($p) => new BordereauPassagerDto(
                codeticket: $p['codeticket'],
                nomclient: $p['nomclient'],
                contactclient: $p['contactclient'],
                prix: (float)$p['prix'],
                siegenumero: (int)$p['siegenumero'],
                createdat: $p['createdat'] instanceof \DateTimeInterface ? $p['createdat']->format('d/m/Y H:i') : $p['createdat']
            ),
            $rawPassagers
        );

        return new BordereauOutput(
            voyage: new BordereauVoyageDto(
                id: $voyage->getId(),
                codevoyage: $voyage->getCodevoyage(),
                provenance: $voyage->getProvenance(),
                destination: $voyage->getDestination(),
                datedebut: $voyage->getDatedebut()?->format('d/m/Y H:i') ?? '',
                placestotal: $voyage->getPlacestotal(),
                placesoccupees: $voyage->getPlacesoccupees()
            ),
            gare: new BordereauGareDto(
                id: $gare->getId(),
                libelle: $gare->getLibelle(),
                ville: $gare->getVille()
            ),
            nbtickets: $stats['nbtickets'],
            recette: $stats['recette'],
            placesrestantes: $voyage->getPlacestotal() - $voyage->getPlacesoccupees(),
            generele: (new \DateTime())->format('d/m/Y à H:i'),
            passagers: $passagers
        );
    }
}
