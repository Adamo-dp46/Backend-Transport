<?php

namespace App\Domain\Enum;

enum BagageStatus: string
{
    case STATUT_ENREGISTRE = 'ENREGISTRE';
    case STATUT_EMBARQUE = 'EMBARQUE'; // Lorsqu'un voyage est affecté
    case STATUT_LIVRE = 'LIVRE'; // Auto quand le voyage est clôturé
    case STATUT_PERDU = 'PERDU';
}