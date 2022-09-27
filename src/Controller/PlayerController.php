<?php

namespace App\Controller;

use App\Entity\Player;
use App\Services\EntitySerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;

#[Route('/api')]
class PlayerController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, private EntitySerializer $serializer)
    {}
    
    #[Route('/player', name: 'player')]
    public function index(Request $request): Response
    {
        $response = new Response();

        try {
            $repository = $this->doctrine->getRepository(Player::class);
            
            $name = $request->query->get('name') ? $request->query->get('name') : $request->request->get('name');
            $location = $request->query->get('location') ? $request->query->get('location') : $request->request->get('location');
            
            $player = $repository->findOnePlayerByNameAndOrLocation($name, $location);

            $response->setContent($this->serializer->serialize($player));
            $response->setStatusCode(Response::HTTP_OK);

            return $response;
        } catch(\throwable $e) {
            $response->setContent($e->getMessage());
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

            return $response;
        }
    }
}
