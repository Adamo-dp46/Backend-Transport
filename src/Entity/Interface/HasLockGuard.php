<?php

namespace App\Entity\Interface;

interface HasLockGuard
{
    public function isVerrouille(): ?bool;
}