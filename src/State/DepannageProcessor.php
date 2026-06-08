<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\DepannageStatus;
use App\Domain\Enum\Referencetype;
use App\Domain\Enum\Typemouvement;
use App\Domain\Service\CarStatutService;
use App\Domain\Service\StockmouvementService;
use App\Entity\Depannage;
use App\Entity\Detaildepannage;
use App\Entity\Dto\DepannageInput;
use App\Entity\User;
use App\Repository\CarRepository;
use App\Repository\DepannageRepository;
use App\Repository\PieceRepository;
use App\Repository\TypepanneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepannageProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private CarRepository $carRepository,
        private PieceRepository $pieceRepository,
        private EntityManagerInterface $em,
        private StockmouvementService $stockmouvementService,
        private DepannageRepository $depannageRepository,
        private TypepanneRepository $typepanneRepository,
        private CarStatutService $carStatutService
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var DepannageInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        if($operation instanceof Post) {
            return $this->handlePost($data, $user->getId(), $entrepriseId, $operation, $uriVariables, $context);
        }

        if($operation instanceof Patch) {
            return $this->handlePatch($data, $user->getId(), $entrepriseId, $operation, $uriVariables, $context);
        }
    }

    private function handlePost($data, $userId, $entrepriseId, $operation, $uriVariables, $context)
    {
        /** @var DepannageInput $data */

        $car = $this->getCar($data->car, $entrepriseId);
        $typepanne = $this->getTypepanne($data->typepanne, $entrepriseId);
        // $this->carStatutService->verifierDisponibiliteDepannage($car); -- Pour vérifier si le car peut être mis en dépannage
        $depannage = new Depannage();
        $depannage
            ->setLieudepannage($data->lieudepannage)
            ->setDescription($data->description)
            ->setIdentreprise($entrepriseId)
            ->setCar($car)
            ->setTypepanne($typepanne)
            ->setCreatedBy($userId)
            ->setDatedepannage(new \DateTimeImmutable()) // Ou le reçevoir via le 'input'
        ;
        $this->carStatutService->mettreEnPanne($car); // Pour indiquer que le car est en panne
        $this->em->persist($depannage);
        $this->em->flush(); /*
            - Va être nécessaire pour avoir l'id vu qu'on utilise un 'input'
        */
        $this->handleDetails($depannage, $data->details, $entrepriseId, $userId);
        return $this->processor->process($depannage, $operation, $uriVariables, $context);
    }

    private function handlePatch($data, $userId, $entrepriseId, $operation, $uriVariables, $context)
    {
        /**
         * @var Depannage
         */
        $depannage = $this->depannageRepository->findOneBy([
            'id' => $uriVariables['id'],
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if(!$depannage) {
            throw new NotFoundHttpException('Dépannage invalide');
        }

        if($depannage->getStatut() === DepannageStatus::CLOTURE->value) {
            throw new BadRequestHttpException('Dépannage clôturé non modifiable');
        }
        /*
            if(isset($data->car)) {
                $car = $this->getCar($data->car, $entrepriseId);
                $depannage->setCar($car);
            }
        */
        if(isset($data->car)) {
            $newCar = $this->getCar($data->car, $entrepriseId);
            $oldCar = $depannage->getCar();
            if($oldCar->getId() !== $newCar->getId()) {
                $this->carStatutService->mettreDisponible($oldCar); /*
                    - L'ancien car redevient disponible et le nouveau en panne
                */
                // $this->carStatutService->verifierDisponibiliteDepannage($newCar); -- .. si un car en voyage peut être mise en panne
                $this->carStatutService->mettreEnPanne($newCar);
            }
            $depannage->setCar($newCar);
        }

        if(isset($data->typepanne)) {
            $typepanne = $this->getTypepanne($data->typepanne, $entrepriseId);
            $depannage->setTypepanne($typepanne);
        }

        $depannage
            ->setLieudepannage($data->lieudepannage ?? $depannage->getLieudepannage())
            ->setDescription($data->description ?? $depannage->getDescription())
            ->setUpdatedBy($userId);

        if(!empty($data->details)) {
            foreach($depannage->getDetaildepannages() as $detail) { /*
                - On annule les anciens mouvements
            */
                $this->stockmouvementService->createMovement(
                    $detail->getPiece(),
                    Typemouvement::ENTREE->value,
                    $detail->getQuantite(),
                    Referencetype::DEPANNAGE->value,
                    $depannage->getId(),
                    $entrepriseId,
                    $userId
                );
                $this->em->remove($detail);
            }
        }

        $this->handleDetails($depannage, $data->details, $entrepriseId, $userId);

        return $this->processor->process($depannage, $operation, $uriVariables, $context);
    }

    private function handleDetails($depannage, $details, $entrepriseId, $userId)
    {
        $ids = array_map(fn($d) => $d['piece'], $details);
        if(count($ids) !== count(array_unique($ids))) {
            throw new BadRequestHttpException('Une pièce est en doublon dans ce dépannage');
        }

        $total = 0;
        foreach($details as $detailInput) {
            $piece = $this->pieceRepository->findOneBy([
                'id' => $detailInput['piece'],
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]);

            if(!$piece) {
                throw new NotFoundHttpException('Pièce invalide');
            }

            $prixunitaire = $detailInput['prixunitaire'] ?? $piece->getPrixUnitaire();
            $quantite = $detailInput['quantite'];

            if($quantite <= 0) { // Vu qu'on n'a de pas règle dessus
                throw new BadRequestHttpException('Quantité invalide');
            }

            if ($prixunitaire <= 0) {
                throw new BadRequestHttpException('Prix unitaire invalide');
            }

            $detail = new Detaildepannage();
            $detail
                ->setPiece($piece)
                ->setQuantite($quantite)
                ->setDepannage($depannage)
                ->setPrixunitaire($prixunitaire); /*
                    ->setCouttotal($coutTotalLigne) -- On peut '$coutTotalLigne = $prixunitaire * $quantite' si on veut le cout total par détail
                */
            $this->em->persist($detail);

            # On crée un mouvement stock
            $this->stockmouvementService->createMovement(
                $piece,
                Typemouvement::SORTIE->value,
                $quantite,
                Referencetype::DEPANNAGE->value,
                $depannage->getId(),
                $entrepriseId,
                $userId
            );

            $total += $prixunitaire * $quantite;
        }
        $depannage->setCoutTotal($total);
    }

    private function getCar(int $carId, int $entrepriseId)
    {
        $car = $this->carRepository->findOneBy([
            'id' => $carId,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        if(!$car) {
            throw new NotFoundHttpException('Car invalide');
        }
        return $car;
    }

    private function getTypepanne(int $typepanneId, int $entrepriseId)
    {
        $typepanne = $this->typepanneRepository->findOneBy([
            'id' => $typepanneId,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        if(!$typepanne) {
            throw new NotFoundHttpException('Type de panne invalide');
        }
        return $typepanne;
    }
}
