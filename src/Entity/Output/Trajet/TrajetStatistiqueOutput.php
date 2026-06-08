<?php

namespace App\Entity\Output\Trajet;

final class TrajetStatistiqueOutput
{
    public function __construct(
        public readonly int   $totalTrajets,
        /** @var TrajetPerformanceDto[] */
        public readonly array $performances,
    )
    {
    }
}