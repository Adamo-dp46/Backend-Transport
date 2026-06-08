<?php

namespace App\Domain\Enum;

enum DepannageStatus: string
{
    case EN_COURS = 'EN COURS';

    case CLOTURE = 'CLOTURE';
}