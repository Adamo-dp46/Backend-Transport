<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\CourrierStatus;
use App\Entity\Courrier;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LivrerCourrierProcessor implements ProcessorInterface
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

        if($data->getStatut() !== CourrierStatus::STATUT_RECEPTIONNE->value) {
            throw new BadRequestHttpException('Seul un courrier réceptionné peut être marqué comme livré. Statut actuel : ' . $data->getStatut());
        }
        $data
            ->setStatut(CourrierStatus::STATUT_LIVRE->value)
            ->setUpdatedBy($user->getId())
            ->setUpdatedAt(new \DateTimeImmutable());
        /*
            if($data->getModepaiement() === Courrier::PAIEMENT_RECEPTION) {
                $data
                    ->setEtatpaiement(Courrier::ETAT_PAIEMENT_PAYE)
                    ->setDatepaiement(new \DateTimeImmutable());
            } -- Si paiement à la réception, on le marque comme payé maintenant
        */
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
