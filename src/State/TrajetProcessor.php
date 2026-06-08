<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Dto\TrajetInput;
use App\Entity\Trajet;
use App\Entity\User;
use App\Repository\TarifRepository;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TrajetProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private Security $security,
        private EntityManagerInterface $em,
        private TarifRepository $tarifRepository,
        private TrajetRepository $trajetRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var TrajetInput $data */

        /**
         * @var User
         */
        $user = $this->security->getUser();
        $entrepriseId = $user->getEntreprise()->getId();

        $trajet = $this->trajetRepository->findOneBy([
            'provenance' => $data->provenance,
            'destination' => $data->destination,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        if($trajet) {
            throw new ConflictHttpException('Le trajet existe déjà pour cette entreprise');
        }

        $tarif = $this->tarifRepository->findOneBy([
            'id' => $data->tarifId,
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        if(!$tarif) {
            throw new BadRequestHttpException('Tarif invalide pour cette entreprise');
        }

        if($data->provenance === $data->destination) {
            throw new BadRequestHttpException('La provenance et la destination ne doivent pas être identiques');
        }

        $trajet = new Trajet();
        $trajet
            ->setProvenance($data->provenance)
            ->setDestination($data->destination)
            ->setIdentreprise($entrepriseId)
            ->setTarif($tarif)
            ->setCreatedBy($user->getId())
            ->setCodeTrajet($this->generateCodeTrajet($entrepriseId, $data->provenance, $data->destination))
        ;
        return $this->processor->process($trajet, $operation, $uriVariables, $context);
    }

    private function generateCodeTrajet(int $entrepriseId, string $provenance, string $destination): string
    {
        $count = $this->em->getRepository(Trajet::class)->count([
            'identreprise' => $entrepriseId,
            'deletedAt' => null
        ]);
        $from = $this->slugify($provenance);
        $to = $this->slugify($destination);
        return sprintf('TRJ-%s-%s-%04d', $from, $to, $count + 1); /*
            - Ou.. 'return sprintf('TR-%d-%04d', $entrepriseId, $count + 1)'
        */
    }

    private function slugify(string $value): string
    {
        $value = transliterator_transliterate('Any-Latin; Latin-ASCII; Upper()', $value); /*
            - 'transliterator_transliterate' nécessite l'extension 'intl'
        */
        $value = preg_replace('/[^A-Z0-9]+/', '-', $value); /*
            - Pour supprimer les accents puis on garde les lettres et chiffres et on remplace le reste par '-', on supprime les tirets en début-fin et limite à 10 caractères
        */
        return substr(trim($value, '-'), 0, 10);
        /*
            - Ou..
            $value = strtoupper($value);
            $accents = [
                'À'=>'A','Â'=>'A','Ä'=>'A','Á'=>'A','Ã'=>'A',
                'È'=>'E','Ê'=>'E','Ë'=>'E','É'=>'E',
                'Î'=>'I','Ï'=>'I','Í'=>'I','Ì'=>'I',
                'Ô'=>'O','Ö'=>'O','Ó'=>'O','Ò'=>'O','Õ'=>'O',
                'Ù'=>'U','Û'=>'U','Ü'=>'U','Ú'=>'U',
                'Ç'=>'C','Ñ'=>'N','Ý'=>'Y'
            ];
            $value = strtr($value, $accents);
            $value = preg_replace('/[^A-Z0-9]+/', '-', $value);
            return substr(trim($value, '-'), 0, 10);
        */
    }
}
