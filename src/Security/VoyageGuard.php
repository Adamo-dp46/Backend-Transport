<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Voyage;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Droits sur un voyage selon la POSITION de la gare de l'agent sur la ligne.
 *  - Préparation (création, modification, affectation car/personnel, suppression) : tout le monde
 *    SAUF la gare de DESTINATION (qui ne fait que clôturer). Admin/super : toujours.
 *  - Clôture : réservée à la gare de DESTINATION (terminus) ou à un admin.
 *  - Réception : réservée à une gare INTERMÉDIAIRE (sur la ligne, ni origine ni terminus), voyage non clôturé.
 */
class VoyageGuard
{
    private function isAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true)
            || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    public function assertPeutGerer(User $user, ?Voyage $voyage): void
    {
        if ($this->isAdmin($user)) {
            return;
        }
        $terminus = $voyage?->getLigne()?->getGareterminus();
        if ($terminus !== null && $user->getGare()?->getId() === $terminus->getId()) {
            throw new BadRequestHttpException('La gare de destination ne peut pas préparer ce voyage : elle ne fait que le clôturer');
        }
    }

    public function assertPeutCloturer(User $user, Voyage $voyage): void
    {
        if ($this->isAdmin($user)) {
            return;
        }
        $terminus = $voyage->getLigne()?->getGareterminus();
        if ($terminus === null) {
            return; // voyage sans ligne (legacy) : pas de restriction
        }
        if ($user->getGare()?->getId() !== $terminus->getId()) {
            throw new BadRequestHttpException('Seule la gare de destination peut clôturer ce voyage');
        }
    }

    public function assertPeutReceptionner(User $user, Voyage $voyage): void
    {
        $gare = $user->getGare();
        if ($gare === null) {
            throw new BadRequestHttpException('Vous devez être rattaché à une gare pour réceptionner un voyage');
        }
        if ($voyage->getDatefin() !== null) {
            throw new BadRequestHttpException('Ce voyage est clôturé : il ne peut plus être réceptionné');
        }
        $ligne = $voyage->getLigne();
        if ($ligne === null) {
            throw new BadRequestHttpException('Ce voyage n\'est rattaché à aucune ligne');
        }

        $surLigne = false;
        foreach ($ligne->getArrets() as $arret) {
            if ($arret->getGare()->getId() === $gare->getId()) {
                $surLigne = true;
                break;
            }
        }
        if (!$surLigne) {
            throw new BadRequestHttpException('Votre gare (' . $gare->getLibelle() . ') n\'est pas desservie par ce voyage');
        }
        if ($gare->getId() === $ligne->getGareorigine()?->getId()) {
            throw new BadRequestHttpException('La gare de provenance ne réceptionne pas un voyage');
        }
        if ($gare->getId() === $ligne->getGareterminus()?->getId()) {
            throw new BadRequestHttpException('La gare de destination ne réceptionne pas le voyage : elle le clôture');
        }
    }
}
