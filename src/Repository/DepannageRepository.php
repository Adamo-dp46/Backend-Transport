<?php

namespace App\Repository;

use App\Entity\Depannage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Depannage>
 */
class DepannageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Depannage::class);
    }

    // -- Statistiques -- //

    public function coutTotal(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): float
    {
        $row = $this->createQueryBuilder('d')
            ->select('SUM(d.couttotal) AS total')
            ->andWhere('d.identreprise = :ide')
            ->andWhere('d.datedepannage >= :debut')
            ->andWhere('d.datedepannage <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleResult();

        return round((float)($row['total'] ?? 0), 2);
    }

    public function coutParJour(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('d')
            ->select('DATE(d.datedepannage) AS label, SUM(d.couttotal) AS montant')
            ->andWhere('d.identreprise = :ide')
            ->andWhere('d.datedepannage >= :debut')
            ->andWhere('d.datedepannage <= :fin')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('label')
            ->orderBy('label', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    // -- Flotte -- //
    public function countParVehicule(int $identreprise): array
    {
        return $this->createQueryBuilder('d')
            ->select('c.matricule, COUNT(d.id) AS nbrdepannages')
            ->join('d.car', 'c')
            ->andWhere('d.identreprise = :ide')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->groupBy('c.matricule')
            ->orderBy('nbrdepannages', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }

    public function coutParVehicule(int $identreprise): array
    {
        return $this->createQueryBuilder('d')
            ->select('c.matricule, SUM(d.couttotal) AS couttotal')
            ->join('d.car', 'c')
            ->andWhere('d.identreprise = :ide')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->groupBy('c.matricule')
            ->orderBy('couttotal', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();
    }

    // -- FlotteActivity -- //

    public function countParCar(\DateTimeImmutable $debut, \DateTimeImmutable $fin, int $identreprise): array
    {
        return $this->createQueryBuilder('d')
            ->select('IDENTITY(d.car) AS carid, COUNT(d.id) AS nbdepannages')
            ->andWhere('d.identreprise = :ide')
            ->andWhere('d.datedepannage >= :debut')
            ->andWhere('d.datedepannage <= :fin')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('ide', $identreprise)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('d.car')
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return Depannage[] Returns an array of Depannage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Depannage
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
