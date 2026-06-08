<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\BagageStatus;
use App\Entity\Bagage;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EmbarquerBagageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Bagage $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();

        if($data->getStatut() !== BagageStatus::STATUT_ENREGISTRE->value) {
            throw new BadRequestHttpException('Seul un bagage enregistré peut être embarqué. Statut actuel : ' . $data->getStatut());
        }
        /*
            if($data->getVoyage()->getDateDebut() === null) { -- Vu que le 'datedebut' n'est pas null dans notre cas
                throw new BadRequestHttpException('Le voyage n\'est pas encore démarré');
            }
        */

        if($data->getVoyage()->getDatefin() !== null) {
            throw new BadRequestHttpException('Ce voyage est clôturé, embarquement impossible');
        }

        $data
            ->setStatut(BagageStatus::STATUT_EMBARQUE->value)
            ->setUpdatedBy($user->getId())
            ->setUpdatedAt(new \DateTimeImmutable())
        ;

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
