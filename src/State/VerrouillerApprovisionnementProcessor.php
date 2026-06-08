<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Approvisionnement;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class VerrouillerApprovisionnementProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Approvisionnement $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $data
            ->setVerrouille(!$data->isVerrouille())
            ->setUpdatedBy($user->getId())
        ;
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
