<?php

namespace App\Entity\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class TrajetInput
{
    #[Assert\NotBlank]
    #[Groups(['write:TrajetInput'])]
    public string $provenance;

    #[Assert\NotBlank]
    #[Groups(['write:TrajetInput'])]
    public string $destination;

    #[Assert\NotNull]
    #[Groups(['write:TrajetInput'])]
    public int $tarifId;

    /*
        #[Groups(['write:TrajetInput'])]
        public string $datedebut;

        #[Groups(['write:TrajetInput'])]
        public ?int $carId = null;
    */
}