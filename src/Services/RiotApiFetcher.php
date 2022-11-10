<?php

namespace App\Services;

use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\PlayerRepository;
use App\Repository\GameRepository;

class RiotApiFetcher
{
	private $api_key; 

	public function __construct(private ParameterBagInterface $container, private HttpClientInterface $client, private PlayerRepository $playerRepository, private GameRepository $gameRepository)
	{
		$this->api_key = $this->getApiKey();
	}
	
	/**
	 * Tape sur l'api externe pour récupérer les données du joueur et ses games pour insérer le tout en bdd
	 *
	 * @param string $playerParam - nom ou pseudo du joueur LoL
     * @param $entityManager - doctrine manager pour persister et insérer l'entité en bdd
	 *
	 * @return Player
	 */
	public function populateOrUpdateDatabaseWithPlayer(string $playerParam, $entityManager): Player
	{
		// api -> get des joueurs
        $jsonSummoner = self::fetch('https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/'.$playerParam, $this->api_key, $this->client);
		        
        // api -> fetch les games du joueur
		$jsonGames = self::fetch('https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/'.$jsonSummoner['puuid'].'/ids?start=0&count=20', $this->api_key, $this->client);

		// persist ou update en bdd
		$existingPlayer = $this->playerRepository->findOneBy(['puuid' => $jsonSummoner['puuid']]);
        
		$summoner = $existingPlayer == null ? new Player() : $existingPlayer;
        $summoner->setName($jsonSummoner['name']);
        $summoner->setPuuid($jsonSummoner['puuid']);
        $summoner->setLocation('europe');
        $existingPlayer == null ? $entityManager->persist($summoner) : null;

		$spells = self::fetch('http://ddragon.leagueoflegends.com/cdn/12.18.1/data/en_US/summoner.json', null, $this->client, false);

        // loop les parties -> api -> get les timelines de chaque partie
        foreach ($jsonGames as $_game) {
			$gameDetailData = self::fetch('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game, $this->api_key, $this->client);
			$gameTimeline = self::fetch('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game."/timeline", $this->api_key,  $this->client);
            
			$recap = self::sortJsonData($gameDetailData, $spells);

			$exisistingGame = $this->gameRepository->findOneBy(['uuid' => $_game]);

			$game = $exisistingGame == null ? new Game() : $exisistingGame;
			
            $game->setUuid($_game);
            $game->setTimeline($gameTimeline);
			$game->setRecap($recap);
            $game->addPlayer($summoner);

            $exisistingGame == null ? $entityManager->persist($game) : null;
        }

        $entityManager->flush();

		return $summoner;
	}
	
	/**
	 * Fetch sur une adresse api
	 *
	 * @param string $url - url à appeler sur l'api
	 * @param bool $isRiotApi - détermine si l'url donnée est sur RiotGames ou non (nécessaire pour savoir si la clé api doit être fournie dans le header)
	 * @param bool $jsonToAssociativeArray - détermine si le résultat doit être converti en tableau
	 *
	 * @return array | \stdClass
	 */
	private static function fetch(string $url, string | null $apiKey, HttpClientInterface $client, bool | null $isRiotApi = true): array | \stdClass
	{
        if($isRiotApi) {
            $opts = [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Riot-Token' => $apiKey
                ]
            ];
        } else {
            $opts = [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ];
        }

        $response = $client->request(
            'GET',
            $url,
            $opts
        );

		return $response->toArray();
	}
	
	/**
	 * Trie et récupère les données pertinentes de l'api riot
	 *
	 * @param array | \stdClass $gameDetailData - le détail d'un match préalablement récupéré sur l'api
     * @param array | \stdClass $spells
	 *
	 * @return array
	 */
	private static function sortJsonData(array | \stdClass $gameDetailData, array | \stdClass $spells): array
	{
		$output = [];

		$output['game_duration'] = $gameDetailData['info']['gameDuration'];
		$output['game_creation'] = $gameDetailData['info']['gameCreation'];
		$output['game_mode'] = $gameDetailData['info']['gameMode'];

		for ($i = 0; $i < count($gameDetailData['info']['participants']); $i++) {
			$output['participants'][$i]['summonerName'] = $gameDetailData['info']['participants'][$i]['summonerName'];
			$output['participants'][$i]['teamId'] = $gameDetailData['info']['participants'][$i]['teamId'];
			$output['participants'][$i]['championId'] = $gameDetailData['info']['participants'][$i]['championId'];
			$output['participants'][$i]['championName'] = $gameDetailData['info']['participants'][$i]['championName'];
			$output['participants'][$i]['champlevel'] = $gameDetailData['info']['participants'][$i]['champLevel'];
			$output['participants'][$i]['role'] = $gameDetailData['info']['participants'][$i]['role'];
			$output['participants'][$i]['kills'] = $gameDetailData['info']['participants'][$i]['kills'];
			$output['participants'][$i]['deaths'] = $gameDetailData['info']['participants'][$i]['deaths'];
			$output['participants'][$i]['assists'] = $gameDetailData['info']['participants'][$i]['assists'];
			$output['participants'][$i]['totalMinionsKilled'] = $gameDetailData['info']['participants'][$i]['totalMinionsKilled'];
			$output['participants'][$i]['item0'] = $gameDetailData['info']['participants'][$i]['item0'];
			$output['participants'][$i]['item1'] = $gameDetailData['info']['participants'][$i]['item1'];
			$output['participants'][$i]['item2'] = $gameDetailData['info']['participants'][$i]['item2'];
			$output['participants'][$i]['item3'] = $gameDetailData['info']['participants'][$i]['item3'];
			$output['participants'][$i]['item4'] = $gameDetailData['info']['participants'][$i]['item4'];
			$output['participants'][$i]['item5'] = $gameDetailData['info']['participants'][$i]['item5'];
			$output['participants'][$i]['item6'] = $gameDetailData['info']['participants'][$i]['item6'];
			$output['participants'][$i]['visionScore'] = $gameDetailData['info']['participants'][$i]['visionScore'];

			forEach ($spells['data'] as $_spell) {
				if($_spell['key'] == $gameDetailData['info']['participants'][$i]['summoner1Id']) {
					$output['participants'][$i]['summoner1Id'] = $_spell['id'];
				}
				if($_spell['key'] == $gameDetailData['info']['participants'][$i]['summoner2Id']) {
					$output['participants'][$i]['summoner2Id'] = $_spell['id'];
				}
			}
			
			$output['participants'][$i]['goldEarned'] = $gameDetailData['info']['participants'][$i]['goldEarned'];
			$output['participants'][$i]['goldSpent'] = $gameDetailData['info']['participants'][$i]['goldSpent'];
		}

		return $output;
	}

    /**
     * Récupère la clé api RiotGames stockée dans le .env
     *
     * @return string
     */
    public function getApiKey(): string
	{
        return $this->container->get('app.api.key');
	}
}