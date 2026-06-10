<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\EntrepriseRepository;
use App\Repository\GareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private Security $security,
        private EntrepriseRepository $entrepriseRepository,
        private GareRepository $gareRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $data */

        /**
         * @var User
         */
        $currentUser = $this->security->getUser();
        $entrepriseId = $currentUser->getEntreprise()->getId();
        $entreprise = $this->entrepriseRepository->find($entrepriseId);

        if(!empty($data->getPlainPassword())) {
            $data->setPassword(
                $this->hasher->hashPassword(
                    $data,
                    $data->getPlainPassword()
                )
            );
            $data->setPlainPassword(null); // Permet d'éviter de laisser des données sensibles comme le mot de passe en clair en mémoire
        }

        if($operation instanceof Post) {
            $data->setEntreprise($entreprise); // On lui affecte l'entreprise de l'utilisateur qui l'a crée
            if($data->getGare() !== null) {
                $gare = $this->gareRepository->find($data->getGare()->getId());
                if($gare) {
                    if(in_array('ROLE_ADMIN_GARE', $currentUser->getRoles(), true) && $currentUser->getGare()?->getId() !== $gare->getId()) {
                        throw new AccessDeniedHttpException('Vous ne pouvez créer des utilisateurs que pour votre propre gare.');
                    }
                    $data->setGare($gare);
                }
            } elseif(in_array('ROLE_ADMIN_GARE', $currentUser->getRoles(), true)) {
                $data->setGare($currentUser->getGare()); /*
                    - On.. auto affectation si l'administrateur de la gare ne précise pas de gare
                */
            }
        }

        if($operation instanceof Patch) {
            if(in_array('ROLE_ADMIN', $data->getRoles(), true) && !in_array('ROLE_ADMIN', $currentUser->getRoles(), true)
            ) {
                throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé à modifier l\'administrateur'); /*
                    - On empêche la modification de l'admin par un non admin
                */
            }

            if($data->isFounder() && !$currentUser->isFounder()) {
                throw new AccessDeniedHttpException('Seul le fondateur peut modifier son propre compte');
            }

            $existingRoles = $this->em->getRepository(UserRole::class)->findBy([
                'usere' => $data
            ]);
            foreach($existingRoles as $existing) { /*
                - On supprime les anciens 'UserRole' de l'utilisateur ou avoir le 'orphanRemoval: true' et 'cascade: ['persist', 'remove']' sur le 'OneToMany'
            */
                $this->em->remove($existing);
            }
            /*
                - Si on 'write:User' sur '$roles'
                $incomingRoles = $data->getRoles(); -- On.. empêche un non administrateur de s'attribuer 'ROLE_ADMIN'
                if(in_array('ROLE_ADMIN', $incomingRoles, true) && !in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
                    throw new AccessDeniedHttpException('Vous ne pouvez pas attribuer le rôle administrateur');
                }
            */
        }

        foreach($data->getUserRoles() as $userRole) {
            if (!$userRole->getRole()) {
                continue; /*
                    - Pour éviter un rôle 'null' ou '{}' et on peut 'throw' une exception 'BadRequestHttpException' vu qu'on n'a définie 'role' comme nullable dans 'UserRole'
                */
            }
            $userRole
                ->setUsere($data)
                ->setIdentreprise($entreprise->getId())
                ->setCreatedBy($currentUser->getId()); /*
                - On ne persist pas vu qu'on n'a le 'cascade: ['persist']' sur 'User'
            */
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
