<?php

namespace App\Entity\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class BagageInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:BagageInput'])]
    public string $nomclient;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:BagageInput'])]
    public string $contactclient;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:BagageInput'])]
    public string $nature;

    #[Assert\Choice(choices: ['LEGER', 'LOURD', 'VOLUMINEUX', 'FRAGILE'])]
    #[Groups(['write:BagageInput'])]
    public string $type;

    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['write:BagageInput'])]
    public int $poids;

    #[Assert\PositiveOrZero]
    #[Groups(['write:BagageInput'])]
    public ?int $montant = null; /*
        - Si fourni → montant forcé par l'agent
    */

    #[Groups(['write:BagageInput'])]
    public ?int $voyage = null;

    // Gare d'origine. Pour un agent rattaché à une gare, elle est forcée à SA gare côté processor ;
    // sinon elle vaut l'origine de la ligne par défaut. Doit être un arrêt de la ligne du voyage.
    #[Groups(['write:BagageInput'])]
    public ?int $garedepart = null;

    // Gare de descente (où le client récupère). Nullable : définie seulement si un voyage est choisi,
    // et doit alors être un arrêt de la ligne du voyage, situé après la gare d'origine.
    #[Groups(['write:BagageInput'])]
    public ?int $garedescente = null;
}