<?php

namespace App\Entity\Output\Trajet;

final class TrajetPerformanceDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $provenance,
        public readonly string $destination,
        public readonly string $codetrajet,
        public readonly int $nbvoyages,
        public readonly int $nbtickets,
        public readonly float $recette
    )
    {
    }
}