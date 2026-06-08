<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Car;
use App\Entity\Siege;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CarProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /**
         * @var User
         */
        $user = $this->security->getUser();
        $identreprise = $user->getEntreprise()->getId();

        if($operation instanceof Post) {
            $data
                ->setIdentreprise($identreprise)
                ->setCreatedBy($user->getId());
            if($data->getSiegesGauche() !== null || $data->getSiegesDroite() !== null) { /*
                - La génération ou régénération des sièges
            */
                $this->regenererSieges($data, $identreprise);
            }
        }

        if($operation instanceof Patch) {
            $data->setUpdatedBy($user->getId());
            $original = $this->em->getUnitOfWork()->getOriginalEntityData($data);
            $gaucheChange = $data->getSiegesGauche() !== ($original['sieges_gauche'] ?? null); /*
                - 'sieges_gauche' vu qu'il vient de la base de données
            */
            $droiteChange = $data->getSiegesDroite() !== ($original['sieges_droite'] ?? null);

            if($gaucheChange || $droiteChange) { /*
                - On regénère les sièges si une des 2 valeurs change
            */
                $this->regenererSieges($data, $identreprise);
            }
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function regenererSieges(Car $car, int $identreprise) // On peut aussi faire ça dans un event listener ou subscriber pour séparer les responsabilités
    {
        // Bloquer la régénération si des tickets actifs existent sur ce car
        if($car->getId() !== null) {
            $ticketsActifs = $this->em->getRepository(Ticket::class)
                ->createQueryBuilder('t')
                ->join('t.siege', 's')
                ->where('s.car = :car')
                ->andWhere('t.deletedAt IS NULL')
                ->setParameter('car', $car)
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();

            if (!empty($ticketsActifs)) {
                throw new BadRequestHttpException(
                    'Impossible de modifier la disposition des sièges : des tickets actifs existent sur ce véhicule'
                );
            }
        }

        foreach($car->getSieges() as $siege) {
            $car->getSieges()->removeElement($siege);
            $this->em->remove($siege); /*
                - On supprime les anciens sièges
            */
        }

        $siegesGauche = $car->getSiegesGauche() ?? 0;
        $siegesDroite = $car->getSiegesDroite() ?? 0;
        $siegesParRangee = $siegesGauche + $siegesDroite;

        if($siegesParRangee === 0) {
            return;
        }

        $nbrSiege = $car->getNbrsiege() ?? 0;
        $nbrRangees = (int)ceil($nbrSiege / $siegesParRangee);
        $numero = 1;

        for($rangee = 1; $rangee <= $nbrRangees; $rangee++) {
            for ($col = 1; $col <= $siegesGauche; $col++) { /*
                - Le côté gauche et on le traite en premier pour que les numéros de sièges soient attribués de manière séquentielle
            */
                if($numero > $nbrSiege) {
                    break;
                }
                $siege = new Siege();
                $siege
                    ->setNumero($numero++)
                    ->setRangee($rangee)
                    ->setColonne($col)
                    ->setCote('GAUCHE') // Ou enum
                    ->setCar($car)
                    ->setIdentreprise($identreprise)
                ;
                $this->em->persist($siege);
                $car->getSieges()->add($siege);
            }

            for($col = 1; $col <= $siegesDroite; $col++) { /*
                - Le côté droit
            */
                if($numero > $nbrSiege) {
                    break;
                }
                $siege = new Siege();
                $siege
                    ->setNumero($numero++)
                    ->setRangee($rangee)
                    ->setColonne($col)
                    ->setCote('DROITE')
                    ->setCar($car)
                    ->setIdentreprise($identreprise)
                ;
                $this->em->persist($siege);
                $car->getSieges()->add($siege);
            }
        }
    }
}