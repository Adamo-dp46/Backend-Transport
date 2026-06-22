<?php

namespace App\Repository;

use App\Entity\Tarif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tarif>
 */
class TarifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tarif::class);
    }

    /**
     * Prix d'un segment (garedepart → garearrivee) dans la grille GLOBALE de l'entreprise.
     * Indépendant de la ligne : le même couple de gares a un seul prix.
     */
    public function findMontant(int $gareDepartId, int $gareArriveeId, int $entrepriseId): ?Tarif
    {
        return $this->findOneBy([
            'garedepart' => $gareDepartId,
            'garearrivee' => $gareArriveeId,
            'identreprise' => $entrepriseId,
            'deletedAt' => null,
        ]);
    }
}
