<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Enum\Referencetype;
use App\Domain\Enum\Typemouvement;
use App\Domain\Service\StockmouvementService;
use App\Entity\Approvisionnement;
use App\Entity\Detailapprovisionnement;
use App\Entity\Dto\ApprovisionnementInput;
use App\Entity\User;
use App\Repository\ApprovisionnementRepository;
use App\Repository\FournisseurRepository;
use App\Repository\PieceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApprovisionnementProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private FournisseurRepository $fournisseurRepository,
        private PieceRepository $pieceRepository,
        private StockmouvementService $stockmouvementService,
        private ApprovisionnementRepository $approvisionnementRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var ApprovisionnementInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $ids = array_map(fn($d) => $d['piece'], $data->details);
        if(count($ids) !== count(array_unique($ids))) { // Une validation anti-doublon de pièce
            throw new BadRequestHttpException('Une pièce est en doublon dans cet approvisionnement');
        }
        /* -- Ou
            $pieceIds = [];
            foreach($data->details as $detailInput) {
                if(in_array($detailInput['piece'], $pieceIds, true)) {
                    throw new BadRequestHttpException(
                        sprintf('La pièce %d est en doublon dans ce dépannage.', $detailInput['piece'])
                    );
                }
                $pieceIds[] = $detailInput['piece'];
            }
        */
        if($operation instanceof Post) {
            return $this->handlePost($data, $user->getId(), $entrepriseId, $operation, $uriVariables, $context);
        }

        if($operation instanceof Patch) {
            return $this->handlePatch($data, $user->getId(), $entrepriseId, $operation, $uriVariables, $context);
        }
    }

    private function handlePost($data, $userId, $entrepriseId, $operation, $uriVariables, $context)
    {
        $fournisseur = $this->fournisseurRepository->findOneBy([
            'id' => $data->fournisseur,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);

        if(!$fournisseur){
            throw new NotFoundHttpException('Référence invalide');
        }

        $approvisionnement = new Approvisionnement();
        $approvisionnement
            ->setFournisseur($fournisseur)
            ->setIdentreprise($entrepriseId)
            ->setCreatedBy($userId)
            ->setDateappro(new \DateTimeImmutable());
        $this->em->persist($approvisionnement);
        $this->em->flush(); /*
            - Va être nécessaire pour avoir l'id vu qu'on utilise un 'input'
        */

        $this->handleDetails($approvisionnement, $data->details, $entrepriseId, $userId);

        return $this->processor->process($approvisionnement, $operation, $uriVariables, $context); /*
            - Pas de '->flush()' vu qu'on a le 'process'
        */
    }

    private function handlePatch($data, $userId, $entrepriseId, $operation, $uriVariables, $context)
    {
        /**
         * @var Approvisionnement
         */
        $approvisionnement = $this->approvisionnementRepository->findOneBy([
            'id' => $uriVariables['id'],
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]); /*
            - Pas de '$context['previous_data']' qui est l'entité récupérée par 'ApiPlatform vu qu'on utilise un 'input' sinon ça va crée une nouvel objet approvisionnement
        */
        if(!$approvisionnement) {
            throw new NotFoundHttpException('Approvisionnement invalide');
        }

        if($approvisionnement->isVerrouille()) {
            throw new BadRequestHttpException('Cet approvisionnement est verrouillé et ne peut plus être modifié');
        }

        /**
         * @var Detailapprovisionnement
         */
        $detailapprovisionnements = $approvisionnement->getDetailapprovisionnements();

        if(!empty($data->details)) {
            foreach($detailapprovisionnements as $detail) { /*
                - On annule les mouvements de stock existants
            */
                $this->stockmouvementService->createMovement(
                    $detail->getPiece(),
                    Typemouvement::SORTIE->value,
                    $detail->getQuantite(),
                    Referencetype::APPROVISIONNEMENT->value,
                    $approvisionnement->getId(),
                    $entrepriseId,
                    $userId
                );
                $this->em->remove($detail);
            }
        }

        $this->handleDetails($approvisionnement, $data->details, $entrepriseId, $userId); /*
            - On recrée les nouveaux détails
        */
        $approvisionnement->setUpdatedBy($userId);

        return $this->processor->process($approvisionnement, $operation, $uriVariables, $context);
    }

    private function handleDetails($approvisionnement, $details, $entrepriseId, $userId)
    {
        # $this->em->wrapInTransaction(function () use ($fournisseur, $entrepriseId, $user, $data, $approvisonnement) -- Au lieu de '->beginTransaction()' pour éviter les incohérences {

        foreach($details as $detailInput) {
            $piece = $this->pieceRepository->findOneBy([
                'id' => $detailInput['piece'],
                'identreprise' => $entrepriseId,
                'deletedAt' => null
            ]); /*
                - '$detailInput->piece' si on utilise 'DetailapprovisionnementInput'
            */
            if(!$piece) {
                throw new NotFoundHttpException('Référence invalide');
            }

            $quantite = (int)$detailInput['quantite'];
            $prixUnitaire = (int)$detailInput['prixunitaire'];

            if($quantite <= 0) {
                throw new BadRequestHttpException('Quantité invalide');
            }
            if ($prixUnitaire <= 0) {
                throw new BadRequestHttpException('Prix unitaire invalide');
            }
            $montantTotal = $quantite * $prixUnitaire;

            $detailapprovisonnement = new Detailapprovisionnement();
            $detailapprovisonnement
                ->setApprovisionnement($approvisionnement)
                ->setPiece($piece)
                ->setQuantite($quantite)
                ->setPrixunitaire($prixUnitaire)
                ->setCouttotal($montantTotal);
            $this->em->persist($detailapprovisonnement);

            # On crée un mouvement stock
            $this->stockmouvementService->createMovement(
                $piece,
                Typemouvement::ENTREE->value,
                $quantite,
                Referencetype::APPROVISIONNEMENT->value,
                $approvisionnement->getId(),
                $entrepriseId,
                $userId
            );
        }
    }
}