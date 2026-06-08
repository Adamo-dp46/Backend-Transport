<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\ReferenceStatus;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SuspendreUserProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $data */

        /**
         * @var User
         */
        $currentUser = $this->security->getUser(); /*
            - On n'a pas besoin de vérifier le 'identreprise' vu qu'il est géré par le filtre
        */
        if(in_array('ROLE_ADMIN', $data->getRoles(), true) && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) { 
            throw new BadRequestHttpException('L\'administrateur ne peut pas être suspendu'); /*
                - On empêche la suspension de l'administrateur
            */
        }

        if($data->isFounder() && !in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
            throw new BadRequestHttpException('Le fondateur de l\'entreprise ne peut pas être suspendu');
        }

        if($data->getId() === $currentUser->getId()) {
            throw new BadRequestHttpException('Vous ne pouvez pas suspendre votre propre compte'); /*
                - On empêche l'auto-suspension
            */
        }

        $nouveauStatut = $data->getStatut() === ReferenceStatus::ACTIF->value ? ReferenceStatus::SUSPENDU->value : ReferenceStatus::ACTIF->value;
        $data->setStatut($nouveauStatut);

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
