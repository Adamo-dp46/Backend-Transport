<?php

namespace App\Repository;

use App\Entity\Trajet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trajet>
 */
class TrajetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trajet::class);
    }

    // -- Statistiques -- //

    public function countTotal(int $identreprise): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllAvecStats(
        \DateTimeImmutable $debut,
        \DateTimeImmutable $fin,
        int $identreprise
    ): array {
        return $this->createQueryBuilder('tr')
            ->select(
                'tr.id',
                'tr.provenance',
                'tr.destination',
                'tr.codetrajet',
                'COUNT(DISTINCT v.id) AS nbvoyages',
                'COUNT(t.id) AS nbtickets',
                'COALESCE(SUM(t.prix), 0) AS recette',
            )
            ->leftJoin('tr.voyages', 'v',  'WITH', 'v.datedebut >= :debut AND v.datedebut <= :fin AND v.deletedAt IS NULL')
            ->leftJoin('v.tickets', 't', 'WITH', 't.createdAt >= :debut AND t.createdAt <= :fin')
            ->andWhere('tr.identreprise = :ide')
            ->andWhere('tr.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('tr.id')
            ->orderBy('recette', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

//    /**
//     * @return Trajet[] Returns an array of Trajet objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Trajet
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
