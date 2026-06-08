<?php

namespace App\Entity\Dto;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CourrierInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:CourrierInput'])]
    public string $nomexpediteur;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:CourrierInput'])]
    public string $contactexpediteur;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:CourrierInput'])]
    public string $nomdestinataire;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['write:CourrierInput'])]
    public string $contactdestinataire;

    #[Assert\NotNull]
    #[Groups(['write:CourrierInput'])]
    public int $gareDepart;

    #[Assert\NotNull]
    #[Groups(['write:CourrierInput'])]
    public int $gareArrivee;

    #[Groups(['write:CourrierInput'])]
    public ?int $voyage = null;

    #[Groups(['write:CourrierInput'])]
    public ?int $fraissuivi = null;

    #[Assert\Choice(choices: ['ENVOI', 'RECEPTION'])]
    #[Groups(['write:CourrierInput'])]
    public string $modepaiement = 'ENVOI';

    #[Assert\NotNull]
    #[Assert\Count(min: 1)]
    #[Groups(['write:CourrierInput'])]
    public array $details = [];
}