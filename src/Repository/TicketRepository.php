<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    // -- Bordereau -- //

    public function findBordereauStats(int $voyageId, int $gareId, int $identreprise): array
    {
        $kpis = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS nbtickets, COALESCE(SUM(t.prix), 0) AS recette')
            ->andWhere('t.voyage = :voyageId')
            ->andWhere('t.gare = :gareId')
            ->andWhere('t.identreprise = :ide')
            ->setParameter('voyageId', $voyageId)
            ->setParameter('gareId', $gareId)
            ->setParameter('ide', $identreprise)
            ->getQuery()
            ->getSingleResult();

        return [
            'nbtickets' => (int)$kpis['nbtickets'],
            'recette' => (float)$kpis['recette']
        ];
    }

    public function findPassagers(int $voyageId, int $gareId, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                't.codeticket',
                't.nomclient',
                't.contactclient',
                't.prix',
                's.numero AS siegenumero',
                't.createdAt AS createdat',
            )
            ->join('t.siege', 's')
            ->andWhere('t.voyage = :voyageId')
            ->andWhere('t.gare = :gareId')
            ->andWhere('t.identreprise = :ide')
            ->setParameter('voyageId', $voyageId)
            ->setParameter('gareId', $gareId)
            ->setParameter('ide', $identreprise)
            ->orderBy('s.numero', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /* Bordereau chauffeur
     */
    public function findByVoyage(int $voyageId, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                't.codeticket',
                't.nomclient',
                't.contactclient',
                't.prix',
                's.numero AS siegenumero',
            )
            ->join('t.siege', 's')
            ->andWhere('t.voyage = :voyageId')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('voyageId', $voyageId)
            ->setParameter('ide', $identreprise)
            ->orderBy('s.numero', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    // -- Statistiques -- //

    public function recettesTotales(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): float
    {
        $row = $this->createQueryBuilder('t')
            ->select('SUM(t.prix) AS total')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleResult();

        return round((float)($row['total'] ?? 0), 2);
    }

    // -- Billetterie -- //

    public function countTotal(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function recettesParJour(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('DATE(t.createdAt) AS label, SUM(t.prix) AS montant, COUNT(t.id) AS nbtickets')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('label')
            ->orderBy('label', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function recettesParTrajet(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('CONCAT(tr.provenance, \' → \', tr.destination) AS trajet, SUM(t.prix) AS montant, COUNT(t.id) AS nbtickets')
            ->join('t.voyage', 'v')
            ->join('v.trajet', 'tr')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('tr.id')
            ->orderBy('montant', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function recettesParCar(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('c.matricule, SUM(t.prix) AS montant, COUNT(t.id) AS nbtickets')
            ->join('t.voyage', 'v')
            ->join('v.car', 'c')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.matricule')
            ->orderBy('montant', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    // -- Agent -- //

    public function performancesParAgent(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                'u.id',
                'u.nom',
                'u.prenom',
                'COUNT(t.id) AS nbtickets',
                'SUM(t.prix) AS recette',
            )
            ->join(User::class, 'u', 'WITH', 'u.id = t.createdBy')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->andWhere('u.entreprise = :ide')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('u.id')
            ->orderBy('recette', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    // -- Caisse -- //

    public function detailParAgentEtVoyage(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                'u.id AS agentid',
                'u.nom',
                'u.prenom',
                'v.codevoyage',
                'v.provenance',
                'v.destination',
                'COUNT(t.id) AS nbtickets',
                'SUM(t.prix) AS recette',
            )
            ->join(User::class, 'u', 'WITH', 'u.id = t.createdBy')
            ->join('t.voyage', 'v')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('u.id, v.id')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('recette', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function detailParJourEtVoyage(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select(
                'DATE(t.createdAt) AS jour',
                'v.codevoyage',
                'v.provenance',
                'v.destination',
                'COUNT(t.id) AS nbtickets',
                'SUM(t.prix) AS recette',
            )
            ->join('t.voyage', 'v')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('jour, v.id')
            ->orderBy('jour', 'ASC')
            ->addOrderBy('recette', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return Ticket[] Returns an array of Ticket objects
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

    //    public function findOneBySomeField($value): ?Ticket
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
