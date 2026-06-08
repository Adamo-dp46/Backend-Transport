<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function findByTrajet(
        int $trajetId,
        int $identreprise,
        int $page = 1,
        int $itemsPerPage = 30,
        ?\DateTimeImmutable $debut = null,
        ?\DateTimeImmutable $fin = null
    ): Paginator
    {
        $offset = ($page - 1) * $itemsPerPage;
        $qb = $this->createQueryBuilder('v')
            ->where('v.trajet = :trajet')
            ->andWhere('v.identreprise = :identreprise')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('trajet', $trajetId)
            ->setParameter('identreprise', $identreprise)
            ->orderBy('v.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($itemsPerPage);

        if($debut) {
            $qb->andWhere('v.datedebut >= :debut')->setParameter('debut', $debut);
        }

        if($fin) {
            $qb->andWhere('v.datedebut <= :fin')->setParameter('fin', $fin);
        }

        return new Paginator($qb->getQuery()); /*
            - Pour que 'ApiPlatform' puisse calculer le 'totalItems' correctement sans un count séparé
        */
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

    /**
     * Taux de remplissage moyen sur la période
     * Retourne ['taux_moyen' => float]
     */
    public function tauxRemplissageMoyen(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): float
    {
        $row = $this->createQueryBuilder('v')
            ->select('AVG(CASE WHEN v.placestotal > 0 THEN (v.placesoccupees * 100.0 / v.placestotal) ELSE 0 END) AS taux_moyen')
            ->andWhere('v.identreprise = :ide')
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleResult();

        return round((float)($row['taux_moyen'] ?? 0), 2);
    }

    /**
     * Détail taux de remplissage par voyage sur la période
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
                'v.placesoccupees',
                'CASE WHEN v.placestotal > 0 THEN ROUND(v.placesoccupees * 100.0 / v.placestotal, 2) ELSE 0 END AS taux'
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
