<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voyage>
 */
class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    /* Statistiques
     */

    /**
     * Nombre total de voyages sur une période
     */
    public function countByPeriode(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Capacité et nb de voyages par gare de DÉPART (ligne.gareorigine), voyages partant sur la période. */
    public function capaciteParGareDepart(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('v')
            ->select('go.id AS gareid, go.libelle AS garelibelle, COUNT(v.id) AS nbvoyages, COALESCE(SUM(v.placestotal), 0) AS capacite')
            ->join('v.ligne', 'l')
            ->join('l.gareorigine', 'go')
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.deletedAt IS NULL')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('go.id')
            ->getQuery()
            ->getArrayResult();
    }

    public function countParJour(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('v')
            ->select("DATE(v.datedebut) AS label, COUNT(v.id) AS total")
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('label')
            ->orderBy('label', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    // La moyenne globale du taux de remplissage est calculée en PHP dans 'ExploitationStatsProvider'
    // (AVG des taux par voyage de 'tauxRemplissageParVoyage'), pas par une requête dédiée.

    /**
     * Détail taux de remplissage par voyage sur la période.
     * 'placesoccupees' = nombre de tickets ACTIFS (VALIDE, deletedAt IS NULL) compté à la volée
     * (l'ancienne colonne stockée a été supprimée ; les billets désistés ne comptent pas).
     * Le 'taux' est calculé côté provider.
     */
    public function tauxRemplissageParVoyage(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('v')
            ->select(
                'v.id AS voyageId',
                'v.codevoyage',
                'v.provenance',
                'v.destination',
                'v.datedebut',
                'v.placestotal',
                "(SELECT COUNT(t.id) FROM App\Entity\Ticket t WHERE t.voyage = v AND t.deletedAt IS NULL AND t.statut = 'VALIDE') AS placesoccupees"
            )
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('v.datedebut', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function countByStatut(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('v')
            ->select(
                'SUM(CASE WHEN v.datefin IS NOT NULL THEN 1 ELSE 0 END) AS termine',
                'SUM(CASE WHEN v.datefin IS NULL AND v.datedebut <= :now THEN 1 ELSE 0 END) AS en_cours',
                'SUM(CASE WHEN v.datefin IS NULL AND v.datedebut > :now THEN 1 ELSE 0 END) AS planifie',
            )
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleResult();
    }

    // -- FlotteActivity -- //

    public function countParCar(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('v')
            ->select('IDENTITY(v.car) AS carid, COUNT(v.id) AS nbvoyages')
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->andWhere('v.car IS NOT NULL')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('v.car')
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return Voyage[] Returns an array of Voyage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Voyage
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
