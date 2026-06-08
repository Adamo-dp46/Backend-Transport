<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TicketProcessor implements ProcessorInterface
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
        /** @var Ticket $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $data
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($user->getId());

        $voyage = $data->getVoyage();
        /*
            return $this->em->wrapInTransaction(function () use ($data, $operation, $uriVariables, $context, $entrepriseId, $user) {
                $this->em->lock($voyage, LockMode::PESSIMISTIC_WRITE); -- Permet d'empêcher que 2 utilisateurs achète le même ticket en même temps
            }); 
        */
        if($voyage->getDatefin() !== null) {
            throw new BadRequestHttpException('Ce voyage est clôturé, la vente de tickets est impossible');
        }

        if(!$voyage->getCar()) {
            throw new BadRequestHttpException('Aucun véhicule affecté à ce voyage');
        }

        if($voyage->getPlacesOccupees() >= $voyage->getPlacesTotal()) {
            throw new BadRequestHttpException('Voyage complet');
        }
        /*
            if($voyage->getStatut() === StatutVoyage::TERMINE) {
                throw new BadRequestHttpException('Voyage terminé');
            }
        */

        $siege = $data->getSiege();
        // 3. Vérifier que le siège est fourni
        if(!$siege) {
            throw new BadRequestHttpException('Siège obligatoire');
        }
        // 4. Vérifier que le siège appartient bien au car du voyage
        if($siege->getCar()->getId() !== $voyage->getCar()->getId()) {
            throw new BadRequestHttpException('Ce siège n\'appartient pas au véhicule affecté au voyage');
        }

        $gare = $data->getGare();
        if(!$gare) {
            throw new BadRequestHttpException('La gare d\'embarquement est obligatoire');
        }
        /*  - Pas obligé à cause du filtre
            if($gare->getIdentreprise() !== $entrepriseId) {
                throw new BadRequestHttpException('Gare invalide');
            }
        */
        $existingTicket = $this->em->getRepository(Ticket::class)->findOneBy([ /*
            - On vérifie que le siège est libre pour ce voyage
        */
            'voyage' => $voyage,
            'siege' => $siege,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
            // 'numero' => $data->getNumero(),
            // 'statut' => 'RESERVE'
        ]);

        if($existingTicket) {
            throw new BadRequestHttpException('Ce siège est déjà occupé pour ce voyage');
        }
        /*
            if($data->getNumero() <= 0 || $data->getNumero() > $voyage->getCar()->getNbrSiege()) {
                throw new BadRequestHttpException('Numéro de siège invalide');
            }
        */
        // 6. Calculer le prix depuis le tarif du trajet
        $tarif = $voyage->getTrajet()?->getTarif();
        if(!$tarif) {
            throw new BadRequestHttpException('Tarif introuvable pour ce trajet');
        }

        // 7. Générer le code ticket
        $codeticket = $voyage->getCodevoyage() . '-' . $this->generateCode($entrepriseId, $voyage->getId());

        $data
            ->setSiege($siege)
            ->setCodeticket($codeticket)
            ->setPrix($tarif->getMontant())
            // ->setNumero($data->getNumero())
            // ->setStatut('RESERVE')
            /*
                ->setVoyage($voyage) -- Vu qu'ils sont dans un groupe de 'denormalization'
                ->setNomclient($data->getNomclient())
                ->setContactclient($data->getContactclient())
            */
        ;
        // 8. Incrémenter les places occupées du voyage
        $voyage->setPlacesOccupees($voyage->getPlacesOccupees() + 1); // Pas recommandé vu qu'on peut faire un 'count' des tickets payé, si on le garde à l'annulation du ticket '-1'

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }

    private function generateCode(int $entrepriseId, int $voyageId): string
    {
        $count = $this->em->getRepository(Ticket::class)->count([
            'identreprise' => $entrepriseId,
            'deletedAt' => null,
            'voyage' => $voyageId
        ]);

        return 'TCK-' . date('Y') . '-' . $count + 1; // Ou.. 'TCK-' . date('YmdHis') . '-' . random_int(1000, 9999)
    }
}
