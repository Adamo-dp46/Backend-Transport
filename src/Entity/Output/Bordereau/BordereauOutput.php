<?php

namespace App\Entity\Output\Bordereau;

final class BordereauOutput
{
    public function __construct(
        public readonly BordereauVoyageDto $voyage,
        public readonly BordereauGareDto   $gare,
        public readonly int $nbtickets,
        public readonly float $recette,
        public readonly int $placesrestantes,
        public readonly string $generele,
        /** @var BordereauPassagerDto[] */
        public readonly array $passagers
    )
    {
    }
}