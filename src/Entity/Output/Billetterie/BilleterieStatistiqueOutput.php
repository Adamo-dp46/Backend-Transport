<?php

namespace App\Entity\Output\Billetterie;

final class BilleterieStatistiqueOutput
{
    public function __construct(
        public readonly int $totalTickets,
        public readonly float $recetteTotale,
        /** @var RecetteParJourDto[] */
        public readonly array $recettesParJour,
        /** @var RecetteParTrajetDto[] */
        public readonly array $recettesParTrajet,
        /** @var RecetteParCarDto[] */
        public readonly array $recettesParCar
    )
    {
    }
}