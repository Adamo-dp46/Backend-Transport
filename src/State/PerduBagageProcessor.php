<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\BagageStatus;
use App\Entity\Bagage;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PerduBagageProcessor implements ProcessorInterface
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

        if(!in_array($data->getStatut(), [
            BagageStatus::STATUT_ENREGISTRE->value, // !!
            BagageStatus::STATUT_EMBARQUE->value,
        ])) {
            throw new BadRequestHttpException('Un bagage livré ne peut pas être déclaré perdu. Statut actuel : ' . $data->getStatut());
        }

        $data
            ->setStatut(BagageStatus::STATUT_PERDU->value)
            ->setUpdatedBy($user->getId())
            ->setUpdatedAt(new \DateTimeImmutable())
        ;

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
