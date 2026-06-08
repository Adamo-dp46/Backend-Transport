<?php

namespace App\Controller\Api;

use App\Domain\Service\EntityDiscoveryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class RoleController extends AbstractController
{
    #[Route('/api/entities', name: 'api_entities', methods: ['GET'])]
    public function entities(EntityDiscoveryService $discovery): JsonResponse
    {
        return $this->json($discovery->getEntityList());
    }
}
