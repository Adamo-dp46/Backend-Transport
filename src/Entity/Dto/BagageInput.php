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

}