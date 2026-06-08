<?php

namespace App\Repository;

use App\Entity\Bagage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bagage>
 */
class BagageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bagage::class);
    }

    /* Statistiques
     */

    public function recettesTotales(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): float
    {
        $row = $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.montant), 0) AS total')
            ->andWhere('b.identreprise = :ide')
            ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.statut IN (:statuts)')
            ->andWhere('b.createdAt >= :debut')
            ->andWhere('b.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('statuts', ['EMBARQUE', 'LIVRE', 'PERDU'])
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleResult()
        ;

        return round((float)($row['total'] ?? 0), 2);
    }

    public function recettesParAgent(
        \DateTimeImmutable $debut,
        \DateTimeImmutable $fin,
        int $identreprise
    ): array
    {
        return $this->createQueryBuilder('b')
            ->select('b.createdBy AS agentid, COALESCE(SUM(b.montant), 0) AS montant, COUNT(b.id) AS nbbagages')
            ->andWhere('b.identreprise = :ide')
            ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.statut IN (:statuts)')
            ->andWhere('b.createdAt >= :debut')
            ->andWhere('b.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('statuts', ['EMBARQUE', 'LIVRE', 'PERDU'])
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('b.createdBy')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function recettesParJourDetail(
        \DateTimeImmutable $debut,
        \DateTimeImmutable $fin,
        int $identreprise
    ): array
    {
        return $this->createQueryBuilder('b')
            ->select(
                'DATE(b.createdAt) AS label',
                'COALESCE(SUM(b.montant), 0) AS montant',
                'COUNT(b.id) AS nbbagages',
                'COALESCE(SUM(b.poids), 0) AS poids',
            )
            ->andWhere('b.identreprise = :ide')
            ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.statut IN (:statuts)')
            ->andWhere('b.createdAt >= :debut')
            ->andWhere('b.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('statuts', ['EMBARQUE', 'LIVRE', 'PERDU'])
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('label')
            ->orderBy('label', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /* Statistiques Bagage
     */
    public function countParStatut(
        \DateTimeImmutable $debut,
        \DateTimeImmutable $fin,
        int $identreprise
    ): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('b.statut, COUNT(b.id) AS total')
            ->andWhere('b.identreprise = :ide')
            ->andWhere('b.createdAt >= :debut')
            ->andWhere('b.createdAt <= :fin')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('b.statut')
            ->getQuery()
            ->getArrayResult()
        ;
        $index = [];
        foreach($rows as $row) {
            $index[$row['statut']] = (int)$row['total'];
        }
        return $index;
    }

    public function poidsTotal(
        \DateTimeImmutable $debut,
        \DateTimeImmutable $fin,
        int $identreprise
    ): int
    {
        $row = $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.poids), 0) AS total')
            ->andWhere('b.identreprise = :ide')
            ->andWhere('b.createdAt >= :debut')
            ->andWhere('b.createdAt <= :fin')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleResult()
        ;
        return (int)($row['total'] ?? 0);
    }

    /* Bordereau chauffeur
     */
    public function findByVoyage(int $voyageId, int $identreprise): array
    {
        return $this->createQueryBuilder('b')
            ->select(
                'b.codebagage',
                'b.nomclient',
                'b.nature',
                'b.type',
                'b.poids',
                'b.montant',
            )
            ->andWhere('b.voyage = :voyageId')
            ->andWhere('b.identreprise = :ide')
            ->andWhere('b.statut != :perdu')
            ->andWhere('b.deletedAt IS NULL')
            ->setParameter('voyageId', $voyageId)
            ->setParameter('ide', $identreprise)
            ->setParameter('perdu', 'PERDU')
            ->orderBy('b.codebagage', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return Bagage[] Returns an array of Bagage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Bagage
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
