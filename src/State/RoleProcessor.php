<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Service\EntityDiscoveryService;
use App\Entity\Role;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class RoleProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityDiscoveryService $entityDiscoveryService
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();
        /**
         * @var Role
         */
        $role = $data;
 
        if($operation->getName() === 'RolePost') {
            $this->handlePost($role, $user, $entrepriseId);
        }

        if($operation->getName() === 'RolePatch') {
            $this->handlePatch($role, $user, $entrepriseId);
        }

        return $this->processor->process($role, $operation, $uriVariables, $context); /*
            - Pas besoin de 'persist' et 'flush' le 'Role' dans le 'processor' car on délègue au 'DoctrineProcessor' interne de 'ApiPlatform' pour ne pas casser des comportements internes
        */
    }

    private function handlePost(Role $role, User $user, int $entrepriseId): void
    {
        $role
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($user->getId())
            ->setUpdatedBy($user->getId());
        $this->syncPermissions($role, $user, $entrepriseId);
    }

    private function handlePatch(Role $role, User $user, int $entrepriseId): void
    {
        $role
            ->setUpdatedBy($user->getId());
        $this->syncPermissions($role, $user, $entrepriseId);
    }

    private function syncPermissions(Role $role, User $user, int $entrepriseId): void
    {
        /*
            - La suppression des permissions existantes en cas d'update est géré par 'orphanRemoval: true'
        */
        $selectedCount = 0;
        foreach($role->getPermissions() as $permission) {
            $permission->setIdentreprise($entrepriseId)
                ->setCreatedBy($user->getId())
                ->setUpdatedBy($user->getId()); /*
                    - On n'a pas 'persist' ici vu qu'on a déjà 'cascade: ['persist']' dans 'Role'
                */
            $selectedCount++;
        }
    }
}
