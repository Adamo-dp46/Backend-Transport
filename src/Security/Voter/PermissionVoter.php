<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\PermissionRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PermissionVoter extends Voter
{
    public function __construct(
        private PermissionRepository $permissionRepository
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VOIR', 'CREER', 'MODIFIER', 'SUPPRIMER', 'IMPRIMER', 'IMPORTER', 'EXPORTER']); # On ne s'occupe que des actions définies dans notre système
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User) {
            return false;
        }

        if(in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        } /*
            - $user = $this->userRepository->find($user->getId()); - Permet d'avoir les 'userRoles' si on utilise le provider 'jwt' ensuite vérifié si l'utilisateur existe
        */
        $entityName = $this->resolveEntityName($subject); /*
            - Plus rapide que 'ReflectionClass'
        */
        if(!$entityName) {
            return false;
        } /*
            - Ou..
            if(is_string($subject)) {
                $entityName = $subject;
            } elseif(is_object($subject)) {
                $entityName = (new \ReflectionClass($subject))->getShortName();
            } else {
                return false;
            }
        */
        $roles = array_map(
            fn($userRole) => $userRole->getRole(),
            $user->getUserRoles()->toArray()
        ); /*
            - On extrait les rôles de l'utilisateur
        */
        if(empty($roles)) {
            return false;
        }
        /*
            foreach($user->getUserRoles() as $userRole) { -- Va faire une requête par rôle utilisateur
                $role = $userRole->getRole();
                $permission = $this->permissionRepository->findOneBy([
                    'role' => $role,
                    'entity' => $entityName,
                    'action' => strtoupper($attribute),
                    'identreprise' => $user->getEntreprise()->getId() // Sinon si 2 entreprises ont le même nom de rôle on aura une collision
                ]);
                if ($permission) {
                    return true;
                }
            }
            return false; -- Ou..
        */
        return $this->permissionRepository->hasPermission( /*
            - Permet d'optimiser les performances en une seule requête
        */
            $roles,
            $entityName,
            strtoupper($attribute),
            $user->getEntreprise()->getId()
        );
    }

    private function resolveEntityName(mixed $subject): ?string
    {
        if(is_string($subject)) {
            return $subject;
        }
        if(is_object($subject)) {
            return substr(strrchr(get_class($subject), '\\'), 1);
        }
        return null;
    }
}
