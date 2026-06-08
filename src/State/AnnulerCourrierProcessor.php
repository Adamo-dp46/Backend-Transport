<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\CourrierStatus;
use App\Entity\Courrier;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnnulerCourrierProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Courrier $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();

        if($data->getStatut() !== CourrierStatus::STATUT_EN_ATTENTE->value) {
            throw new BadRequestHttpException('Seul un courrier en attente peut être annulé. Statut actuel : ' . $data->getStatut());
        }

        $data
            ->setStatut(CourrierStatus::STATUT_ANNULE->value)
            ->setUpdatedBy($user->getId())
            ->setUpdatedAt(new \DateTimeImmutable())
        ;

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
