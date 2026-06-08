<?php

namespace App\Entity\Output\Caisse;

final class CaisseOutput
{
    public function __construct(
        public readonly int $totalTickets,
        public readonly float $recetteTickets,
        public readonly int $totalCourriers,
        public readonly float $recetteCourriers,
        public readonly int $totalBagages,
        public readonly float $recetteBagages,
        public readonly float $recetteTotale,
        /** @var CaisseParAgentDto[] */
        public readonly array $parAgent,
        /** @var CaisseParJourDto[] */
        public readonly array $parJour
    )
    {
    }
}