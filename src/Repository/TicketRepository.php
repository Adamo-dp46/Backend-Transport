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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
        /*
            - Agrégé par LIGNE (et non plus par trajet) : couvre les voyages créés sur une ligne sans trajet.
              Le nom de méthode / le champ 'trajet' du DTO sont conservés (contrat JSON inchangé, dashboard non cassé) ;
              renommage cosmétique 'trajet' -> 'ligne' à faire avec le frontend (étape 3/4).
        */
        return $this->createQueryBuilder('t')
            ->select('COALESCE(l.libelle, l.codeligne) AS trajet, SUM(t.prix) AS montant, COUNT(t.id) AS nbtickets')
            ->join('t.voyage', 'v')
            ->join('v.ligne', 'l')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('l.id')
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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
            ->andWhere("t.statut = 'VALIDE'") // exclut les billets désistés (reportés/annulés) des recettes/bordereaux
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

    // -- Par gare (multi-gares) -- //

    /**
     * Recette billetterie groupée par gare de MONTÉE (gare d'émission du billet).
     */
    public function recetteParGare(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('g.id AS gareid, g.libelle AS garelibelle, COUNT(t.id) AS nbtickets, COALESCE(SUM(t.prix), 0) AS recette')
            ->join('t.gare', 'g')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('g.id')
            ->getQuery()
            ->getArrayResult();
    }

    /** Recette billets par gare (montée) ET par jour — pour les séries temporelles / sparklines. */
    public function recetteParGareEtJour(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('g.id AS gareid, DATE(t.createdAt) AS jour, COALESCE(SUM(t.prix), 0) AS recette')
            ->join('t.gare', 'g')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('g.id')
            ->addGroupBy('jour')
            ->getQuery()
            ->getArrayResult();
    }

    /** Recette billets par gare (montée) ET par agent (createdBy) — croisement caisse. */
    public function recetteParGareEtAgent(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('g.id AS gareid, t.createdBy AS agentid, COUNT(t.id) AS nb, COALESCE(SUM(t.prix), 0) AS recette')
            ->join('t.gare', 'g')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('g.id')
            ->addGroupBy('t.createdBy')
            ->getQuery()
            ->getArrayResult();
    }

    /** Descentes par gare (billets dont garedescente = la gare). Exclut les descentes nulles (legacy). */
    public function descentesParGare(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('g.id AS gareid, g.libelle AS garelibelle, COUNT(t.id) AS nb')
            ->join('t.garedescente', 'g')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('g.id')
            ->getQuery()
            ->getArrayResult();
    }

    /** Top tronçons (gare montée → gare descente) : volume + recette. */
    public function topTroncons(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise, int $limit = 12): array
    {
        return $this->createQueryBuilder('t')
            ->select('gm.id AS deId, gm.libelle AS de, gd.id AS versId, gd.libelle AS vers, COUNT(t.id) AS nb, COALESCE(SUM(t.prix), 0) AS recette')
            ->join('t.gare', 'gm')
            ->join('t.garedescente', 'gd')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('gm.id')
            ->addGroupBy('gd.id')
            ->orderBy('nb', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /** Billets vendus + recette par gare de DÉPART du voyage (ligne.gareorigine), voyages partant sur la période. */
    public function billetsParGareDepart(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('go.id AS gareid, COUNT(t.id) AS billets, COALESCE(SUM(t.prix), 0) AS recette')
            ->join('t.voyage', 'v')
            ->join('v.ligne', 'l')
            ->join('l.gareorigine', 'go')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('v.datedebut >= :debut')
            ->andWhere('v.datedebut <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('go.id')
            ->getQuery()
            ->getArrayResult();
    }

    /** Répartition des billets par statut (VALIDE / REPORTE / ANNULE) — désistements. */
    public function compteParStatut(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.statut AS statut, COUNT(t.id) AS nb')
            ->andWhere('t.identreprise = :ide')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('t.statut')
            ->getQuery()
            ->getArrayResult();
    }

    /** Remises accordées (billets VALIDE, remise > 0) par bénéficiaire (avec sa catégorie). */
    public function remisesParBeneficiaire(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('b.nom AS nom, b.categorie AS categorie, COALESCE(SUM(t.remise), 0) AS total, COUNT(t.id) AS nb')
            ->join('t.beneficiaire', 'b')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.remise > 0')
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('b.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /** Matrice Origine → Destination (billets VALIDE) : volume par couple (montée, descente). */
    public function matriceOD(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select('gm.id AS deId, gm.libelle AS de, gd.id AS versId, gd.libelle AS vers, COUNT(t.id) AS nb')
            ->join('t.gare', 'gm')
            ->join('t.garedescente', 'gd')
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('gm.id')
            ->addGroupBy('gd.id')
            ->getQuery()
            ->getArrayResult();
    }

    /** Billets VALIDE par heure de création (00–23) — heures de pointe. */
    public function billetsParHeure(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('t')
            ->select("DATE_FORMAT(t.createdAt, '%H') AS heure, COUNT(t.id) AS nb")
            ->andWhere('t.identreprise = :ide')
            ->andWhere("t.statut = 'VALIDE'")
            ->andWhere('t.createdAt >= :debut')
            ->andWhere('t.createdAt <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('heure')
            ->orderBy('heure', 'ASC')
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
