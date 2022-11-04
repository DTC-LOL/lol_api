<?php

namespace App\Controller;

use App\Entity\Game;
use App\Services\EntitySerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;

#[Route('/api')]
class GameController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, private EntitySerializer $serializer)
    {}

    #[Route('/game', name: 'app_game')]
    public function index(Request $request): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        try {
            $repository = $this->doctrine->getRepository(Game::class);
            
            $uuid = $request->query->get('uuid') ? $request->query->get('uuid') : $request->request->get('uuid');
            
            $game = $repository->findOneBy(['uuid' => $uuid]);

			if (null == $game) {
				throw $this->createNotFoundException('Aucun joueur trouvé');
			}

            $response->setContent($this->serializer->serialize($game));
            $response->setStatusCode(Response::HTTP_OK);

            return $response;
        } catch(\throwable $e) {
            $response->setContent($e->getMessage());
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

            return $response;
        } 
    }
}
