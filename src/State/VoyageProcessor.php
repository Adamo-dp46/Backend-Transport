<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Service\CarStatutService;
use App\Entity\Car;
use App\Entity\Siege;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Voyage;
use App\Security\VoyageGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class VoyageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private CarStatutService $carStatutService,
        private VoyageGuard $guard
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var Voyage $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        if($operation instanceof Post) {
            $ligne = $data->getLigne();
            if(!$ligne) {
                throw new BadRequestHttpException('La ligne est obligatoire pour créer un voyage');
            }

            // Préparation interdite à la gare de destination de la ligne
            $this->guard->assertPeutGerer($user, $data);

            // Provenance / destination dérivées de la ligne (origine -> terminus)
            $data
                ->setProvenance($ligne->getGareorigine()->getLibelle())
                ->setDestination($ligne->getGareterminus()->getLibelle());

            // Unicité : pas 2 voyages sur la même ligne au même départ
            $existant = $this->em->getRepository(Voyage::class)->findOneBy([
                'ligne' => $ligne,
                'datedebut' => $data->getDatedebut(),
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]);
            if($existant) {
                throw new ConflictHttpException('Un voyage existe déjà pour cette ligne à cette date');
            }

            $data
                ->setIdentreprise($entrepriseId)
                ->setCreatedBy($user->getId())
            ;

            $code = $this->em->getRepository(Voyage::class)->count([
                'ligne' => $ligne,
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]) + 1;
            $data
                ->setCodevoyage($ligne->getCodeligne() . '-V' . $code); /*
                - On peut avoir un problème de concurrence '2 créations en même temps' donc à améliorer
            */
            if($data->getCar()) {
                $this->carStatutService->verifierDisponibiliteVoyage($data->getCar()); /*
                    - On vérifie la disponibilité du car avant d'affecter
                */
                $this->getCar($data);
                $this->carStatutService->mettreEnVoyage($data->getCar());
            } else {
                $data->setPlacesTotal(0);
            }
        }

        if($operation instanceof Patch) {
            $original = $this->em->getUnitOfWork()->getOriginalEntityData($data); /*
                - Pour récupérer l'état original de l'objet depuis la base de données avant les modifications sinon '$data->getDatefin()' nous donne l'état avant modification
            */
            if(!empty($original['datefin'])) {
                throw new BadRequestHttpException('Ce voyage est déjà clôturé et ne peut plus être modifié');
            }

            if($data->getProvenance() === $data->getDestination()) {
                throw new BadRequestHttpException('La provenance et la destination ne peuvent pas être identiques');
            }

            if($data->getDatefin() && $data->getDatefin() <= $data->getDatedebut()) {
                throw new BadRequestHttpException('La date de fin doit être supérieure à la date de départ');
            }

            $data->setUpdatedBy($user->getId());
            /*
                if($data->getCar()) {
                    $this->getCar($data);
                }
            */
            if($data->getDatefin() !== null) {
                // Clôture : réservée à la gare de destination (terminus) — ni la provenance, ni une gare intermédiaire
                $this->guard->assertPeutCloturer($user, $data);
                if($data->getCar()) {
                    $this->carStatutService->mettreDisponible($data->getCar()); /*
                        - Le car devient disponible lors de la clôturation du voyage
                    */
                }
            } else {
                // Modification (hors clôture) : interdite à la gare de destination
                $this->guard->assertPeutGerer($user, $data);

                $oldCarId = $original['car_id'] ?? null; /*
                    - On récupère l'ancine car en cas de changement de car
                */
                $newCar = $data->getCar();
                if($newCar) {
                    $newCarId = $newCar->getId();
                    if($oldCarId !== $newCarId) {
                        if($oldCarId) {
                            $oldCar = $this->em->getRepository(Car::class)->find($oldCarId);
                            if($oldCar) {
                                $this->carStatutService->mettreDisponible($oldCar); /*
                                    - On libère l'ancien car
                                */
                            }
                        }
                        $this->carStatutService->verifierDisponibiliteVoyage($newCar); /*
                            - On vérifie la disponibilité du nouveau car avant d'affecter
                        */
                        $this->carStatutService->mettreEnVoyage($newCar);

                        /* On.. vu que les les sièges sont liés au car et pas au voyage, quand on change le car les anciens tickets pointait vers des sièges de l'ancien car donc on a réaffecter les sièges automatiquement
                         */
                        $tickets = $this->em->getRepository(Ticket::class)->findBy([
                            'voyage' => $data,
                            'deletedAt' => null
                        ]); /*
                            - On récupère les tickets actifs du voyage
                        */
                        foreach($tickets as $ticket) {
                            $ancienNumero = $ticket->getSiege()->getNumero();
                            $nouveauSiege = $this->em->getRepository(Siege::class)->findOneBy([
                                'car' => $data->getCar(),
                                'numero' => $ancienNumero
                            ]); /*
                                - On cherche le siège de même numéro dans le nouveau car
                            */
                            if($nouveauSiege) {
                                $ticket->setSiege($nouveauSiege);
                            } /*
                                - Si le siège n'existe pas dans le nouveau car ou capacité différente.. déjà gérer
                            */
                        }
                    }
                    $this->getCar($data);
                } /*
                    - Si on peut retirer le car du voyage
                    elseif($oldCarId && $newCar === null) {
                        $oldCar = $this->em->getRepository(Car::class)->find($oldCarId);
                        if($oldCar) {
                            $this->carStatutService->mettreDisponible($oldCar);
                        }
                    }
                */
            }
        }
        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function getCar(Voyage $data)
    {
        if($data->getCar()) { /*
            - On vérifie si le car est déjà utilisé sur un autre voyage au même moment
        */
            $existingCarForVoyage = $this->em->getRepository(Voyage::class)
                ->createQueryBuilder('v')
                ->where('v.car = :car')
                ->andWhere('v.id != :currentId OR :currentId IS NULL')
                ->andWhere('v.datefin IS NULL')
                ->andWhere('v.deletedAt IS NULL')
                ->setParameter('car', $data->getCar())
                ->setParameter('currentId', $data->getId())
                ->getQuery()
                ->getOneOrNullResult()
            ;
            if($existingCarForVoyage) {
                throw new BadRequestHttpException(
                    'Ce véhicule est déjà utilisé sur un voyage en cours, clôturez-le avant de l\'affecter à un nouveau voyage'
                );
            }

            $places = $data->getCar()->getNbrSiege();
            if($data->getTicketsCount() > $places) { /*
                - On vérifie que les billets déjà vendus ne dépassent pas la capacité du nouveau car en cas de 'patch'
            */
                throw new BadRequestHttpException('Impossible de changer de Car : les places déjà occupées dépassent la capacité du nouveau véhicule');
            }
            $data->setPlacesTotal($places);
        }
    }
}
