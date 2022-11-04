<?php

namespace App\Services;

use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\PlayerRepository;
use App\Repository\GameRepository;

class RiotApiFetcher
{
	private $api_key; 

	public function __construct(private ParameterBagInterface $container, private PlayerRepository $playerRepository, private GameRepository $gameRepository)
	{
		$this->api_key = $this->getApiKey();
	}
	
	/**
	 * Tape sur l'api externe pour récupérer les données du joueur et ses games pour insérer le tout en bdd
	 *
	 * @param string $playerParam 
	 *
	 * @return array
	 */
	public function populateOrUpdateDatabaseWithPlayer(string $playerParam): array
	{
		// api -> get des joueurs    
        $jsonSummoner = self::fetch('https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/'.$playerParam);
		        
        // api -> fetch les games du joueur
		$jsonGames = self::fetch('https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/'.$jsonSummoner->puuid.'/ids?start=0&count=20');

		// persist ou update en bdd
		$existingPlayer = $playerRepository->findOneBy(['puuid' => $jsonSummoner->puuid]);
        
		$summoner = $existingPlayer == null ? new Player() : $existingPlayer;
        $summoner->setName($jsonSummoner->name);
        $summoner->setPuuid($jsonSummoner->puuid);
        $summoner->setLocation('europe');
        $existingPlayer == null ? $manager->persist($summoner) : null;

		$spells = self::fetch('http://ddragon.leagueoflegends.com/cdn/12.18.1/data/en_US/summoner.json', false);

        // loop les parties -> api -> get les timelines de chaque partie
        foreach ($jsonGames as $_game) {
			$gameDetailData = self::fetch('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game);
			$gameTimeline = self::fetch('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game."/timeline", true, true);
            
			$recap = self::sortJsonData($gameDetailData);

			$exisistingGame = $gameRepository->findOneBy(['uuid' => $_game]);

			$game = $exisistingGame == null ? new Game() : $exisistingGame;
			
            $game->setUuid($_game);
            $game->setTimeline($gameTimeline);
			$game->setRecap($recap);
            $game->addPlayer($summoner);

            $exisistingGame == null ? $manager->persist($game) : null;
            
        }

		return dd($summoner);
        
        // $manager->flush();

		// return $summoner;
	}
	
	/**
	 * Fetch sur une adresse api
	 *
	 * @param string $url 
	 * @param bool $isRiotApi
	 * @param bool $jsonToAssociativeArray
	 *
	 * @return array
	 */
	private static function fetch(string $url, bool | null $isRiotApi = true, bool | null $jsonToAssociativeArray = null): array
	{
		$opts = [];
		if($isRiotApi) {
			$opts = [
				'http' => [
					'method' => "GET",
					'header' => "Accept-language: en\r\n" .
								"X-Riot-Token: " . $this->api_key
				]
			];
		} else {
			$opts = [
				'http' => [
					'method'=>"GET"
				]
			];
		}
		
		$context = stream_context_create($opts);

        $data = file_get_contents($url, true, $context);
    
        $jsonData = json_decode($data, $jsonToAssociativeArray);

		return $jsonData;
	}
	
	/**
	 * Trie et récupère les données pertinentes de l'api riot
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	private static function sortJsonData(array $input): array
	{
		$output = [];

		$output['game_duration'] = $input->info->gameDuration;
		$output['game_creation'] = $input->info->gameCreation;
		$output['game_mode'] = $input->info->gameMode;

		for ($i = 0; $i < count($input->info->participants); $i++) { 
			$output['participants'][$i]['summonerName'] = $input->info->participants[$i]->summonerName;
			$output['participants'][$i]['teamId'] = $input->info->participants[$i]->teamId;
			$output['participants'][$i]['championId'] = $input->info->participants[$i]->championId;
			$output['participants'][$i]['championName'] = $input->info->participants[$i]->championName;
			$output['participants'][$i]['champlevel'] = $input->info->participants[$i]->champLevel;
			$output['participants'][$i]['role'] = $input->info->participants[$i]->role;
			$output['participants'][$i]['kills'] = $input->info->participants[$i]->kills;
			$output['participants'][$i]['deaths'] = $input->info->participants[$i]->deaths;
			$output['participants'][$i]['assists'] = $input->info->participants[$i]->assists;
			$output['participants'][$i]['totalMinionsKilled'] = $input->info->participants[$i]->totalMinionsKilled;
			$output['participants'][$i]['item0'] = $input->info->participants[$i]->item0;
			$output['participants'][$i]['item1'] = $input->info->participants[$i]->item1;
			$output['participants'][$i]['item2'] = $input->info->participants[$i]->item2;
			$output['participants'][$i]['item3'] = $input->info->participants[$i]->item3;
			$output['participants'][$i]['item4'] = $input->info->participants[$i]->item4;
			$output['participants'][$i]['item5'] = $input->info->participants[$i]->item5;
			$output['participants'][$i]['item6'] = $input->info->participants[$i]->item6;
			$output['participants'][$i]['visionScore'] = $input->info->participants[$i]->visionScore;

			forEach (json_decode($spells)->data as $_spell) { 
				if($_spell->key == $input->info->participants[$i]->summoner1Id) {
					$output['participants'][$i]['summoner1Id'] = $_spell->id;
				}
				if($_spell->key == $input->info->participants[$i]->summoner2Id) {
					$output['participants'][$i]['summoner2Id'] = $_spell->id;
				}
			}
			
			$output['participants'][$i]['goldEarned'] = $input->info->participants[$i]->goldEarned;
			$output['participants'][$i]['goldSpent'] = $input->info->participants[$i]->goldSpent;
		}

		return $input;
	}

	public function getApiKey(): string
	{
		$apiKey = $this->container->getParameter('riot_api_key');
		return $apiKey;
	}
}