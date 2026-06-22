<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Service\CarStatutService;
use App\Entity\Dto\AffectcarInput;
use App\Entity\User;
use App\Entity\Voyage;
use App\Security\VoyageGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AffectcarProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private CarStatutService $carStatutService,
        private VoyageGuard $guard
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var AffectcarInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $voyage = $this->em->getRepository(Voyage::class)->findOneBy([
            'id' => $uriVariables['id'],
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if(!$voyage) {
            throw new BadRequestHttpException('Voyage introuvable');
        }

        // Préparation interdite à la gare de destination
        $this->guard->assertPeutGerer($user, $voyage);

        if($voyage->getDatefin() !== null) {
            throw new BadRequestHttpException('Ce voyage est clôturé : impossible d\'affecter un véhicule');
        }

        if($voyage->getCar()) {
            throw new BadRequestHttpException('Un car est déjà affecté à ce voyage');
        }

        $car = $data->car;
        $voyageActif = $this->em->getRepository(Voyage::class)->findOneBy([
            'car' => $car,
            'identreprise' => $entrepriseId,
            'datefin' => null,
            'deletedAt' => null
        ]); /*
            - On vérifie si le car est déjà affecté à un voyage actif
        */
        if($voyageActif && $voyageActif->getId() !== $voyage->getId()) { /*
                - Permet d'éviter le faux positif si l'agent réaffecte le même car au même voyage
            */
            throw new BadRequestHttpException(
                sprintf(
                    'Ce véhicule est déjà affecté au voyage "%s" (%s → %s) qui est en cours. Clôturez ce voyage avant de l\'affecter à un autre.',
                    $voyageActif->getCodevoyage(),
                    $voyageActif->getProvenance(),
                    $voyageActif->getDestination()
                )
            );
        }
        $this->carStatutService->verifierDisponibiliteVoyage($car);
        $this->carStatutService->mettreEnVoyage($car); /*
            - On vérifie la disponibilité du car avant d'affecter
        */
        $voyage->setCar($car);
        $voyage->setPlacesTotal($car->getNbrsiege());

        return $this->processor->process($voyage, $operation, $uriVariables, $context);
    }
}
