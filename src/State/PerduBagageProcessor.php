<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\BagageStatus;
use App\Entity\Bagage;
use App\Entity\User;
use App\Security\GareGuard;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PerduBagageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private GareGuard $gareGuard
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

        if($data->getStatut() === BagageStatus::STATUT_PERDU->value) { /*
            - On autorise la déclaration de perte depuis n'importe quel statut (y compris LIVRE après
              clôture du voyage : le bagage a pu être marqué livré automatiquement sans être arrivé).
              Seul un bagage déjà perdu est refusé.
        */
            throw new BadRequestHttpException('Ce bagage est déjà déclaré perdu');
        }

        // Gare détentrice : pas encore embarqué (ENREGISTRE) → la gare d'origine ; sinon la gare de descente.
        $detentrice = $data->getStatut() === BagageStatus::STATUT_ENREGISTRE->value
            ? $data->getGaredepart()
            : $data->getGaredescente();
        $this->gareGuard->assertEstGare($user, $detentrice, 'Seule la gare détentrice du bagage peut le déclarer perdu');

        $data
            ->setStatut(BagageStatus::STATUT_PERDU->value)
            ->setUpdatedBy($user->getId())
            ->setUpdatedAt(new \DateTimeImmutable())
        ;

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
