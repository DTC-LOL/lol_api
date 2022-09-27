<?php

namespace App\Controller;

use App\Entity\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api')]
class PlayerController extends AbstractController
{
    #[Route('/player', name: 'player')]
    public function index(Request $request): JsonResponse
    {
        $repository = $this->getDoctrine()->getRepository(Player::class);
        
        $name = $request->query->get('name') ? $request->query->get('name') : $request->request->get('name');
        $location = $request->query->get('location') ? $request->query->get('location') : $request->request->get('location');

        $player = $repository->findOnePlayerByNameAndOrLocation($name, $location);

        return $this->json($player);
    }
}
