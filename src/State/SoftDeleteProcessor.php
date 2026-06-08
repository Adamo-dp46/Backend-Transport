<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\DepannageStatus;
use App\Entity\Detailpersonnel;
use App\Entity\Interface\HasLockGuard;
use App\Entity\Interface\HasSoftDeleteGuard;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private RemoveProcessor $removeProcessor,
        private Security $security
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();

        if($data instanceof HasSoftDeleteGuard) { /*
            - On vérifie des blockers si l'entité les supporte
        */
            $blockers = $data->getSoftDeleteBlockers();
            if(!empty($blockers)) {
                throw new UnprocessableEntityHttpException(implode(' ', $blockers));
            }
        }

        if($data instanceof HasLockGuard && $data->isVerrouille()) {
            throw new BadRequestHttpException('Cet élément est verrouillé et ne peut pas être supprimé');
        }

        if($data instanceof Detailpersonnel) {
            if($data->getVoyage()?->getDatefin() !== null) {
                throw new BadRequestHttpException('Impossible de désaffecter un personnel d\'un voyage clôturé');
            }
            if($data->getDepannage()?->getStatut() === DepannageStatus::CLOTURE->value) {
                throw new BadRequestHttpException('Impossible de désaffecter un personnel d\'un dépannage clôturé');
            }
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if(method_exists($data, 'setDeletedAt')) {
            $data
                ->setDeletedAt(new \DateTimeImmutable())
                ->setDeletedBy($user->getId())
                ->setIsEtatdelete(true)
            ;
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
