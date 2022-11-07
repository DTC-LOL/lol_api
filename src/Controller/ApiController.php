<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\RiotApiFetcher;

#[Route('/api')]
class ApiController extends AbstractController
{
	public function __construct(private RiotApiFetcher $apiFetcher, private ManagerRegistry $doctrine)
	{}

	#[Route('/fixtures', name: 'populate_database')]
	public function index(Request $request): Response
	{
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
		
		try {
			$player_name = $request->query->get('name') ? : $request->request->get('name');
			$player = $this->apiFetcher->populateOrUpdateDatabaseWithPlayer($player_name, $this->doctrine->getManager());

            $response->setContent($player->getName().' a été mis à jour ou ajouté dans les joueurs');
            $response->setStatusCode(Response::HTTP_OK);

            return $response;
		} catch(\throwable $e) {
            $response->setContent($e->getMessage());
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

            return $response;
        }
	}
}