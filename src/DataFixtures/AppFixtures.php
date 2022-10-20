<?php

namespace App\DataFixtures;

use App\Entity\Player;
use App\Entity\Game;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{
    public function __construct(private ParameterBagInterface $container)
    {}
    
    public function load(ObjectManager $manager): void
    {
        // api -> get des joueurs
        $opts = array(
            'http'=>array(
            'method'=>"GET",
            'header'=>"Accept-language: en\r\n" .
                        "X-Riot-Token: " . $this->getApiKey()
            )
        );

        $context = stream_context_create($opts);

        $summonerData = file_get_contents('https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/Qrab', true, $context);
    
        $jsonSummoner = json_decode($summonerData);

        $summoner = new Player();
        $summoner->setName($jsonSummoner->name);
        $summoner->setPuuid($jsonSummoner->puuid);
        $summoner->setLocation('europe');
        $manager->persist($summoner);
        
        // loop les joueurs -> api -> get les parties correspondantes
        $gamesData = file_get_contents('https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/'.$jsonSummoner->puuid.'/ids?start=0&count=20', true, $context);

        $jsonGames = json_decode($gamesData);

		$ddragon_opts = array(
            'http'=>array(
            'method'=>"GET"
            )
        );
		$spells = file_get_contents('http://ddragon.leagueoflegends.com/cdn/12.18.1/data/en_US/summoner.json', true);

        // loop les parties -> api -> get les timelines de chaque partie
        foreach ($jsonGames as $_game) {
            $gameDetailData = file_get_contents('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game, false, $context);
			$gameTimeline = file_get_contents('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game."/timeline", false, $context);
            
			$recap = [];

			$recap['game_duration'] = json_decode($gameDetailData)->info->gameDuration;
			$recap['game_creation'] = json_decode($gameDetailData)->info->gameCreation;
			$recap['game_mode'] = json_decode($gameDetailData)->info->gameMode;

			for ($i = 0; $i < count(json_decode($gameDetailData)->info->participants); $i++) { 
				$recap['participants'][$i]['summonerName'] = json_decode($gameDetailData)->info->participants[$i]->summonerName;
                $recap['participants'][$i]['puuid'] = json_decode($gameDetailData)->info->participants[$i]->puuid;
				$recap['participants'][$i]['teamId'] = json_decode($gameDetailData)->info->participants[$i]->teamId;
				$recap['participants'][$i]['championId'] = json_decode($gameDetailData)->info->participants[$i]->championId;
                $recap['participants'][$i]['championName'] = json_decode($gameDetailData)->info->participants[$i]->championName;
				$recap['participants'][$i]['champlevel'] = json_decode($gameDetailData)->info->participants[$i]->champLevel;
				$recap['participants'][$i]['role'] = json_decode($gameDetailData)->info->participants[$i]->role;
				$recap['participants'][$i]['kills'] = json_decode($gameDetailData)->info->participants[$i]->kills;
				$recap['participants'][$i]['deaths'] = json_decode($gameDetailData)->info->participants[$i]->deaths;
				$recap['participants'][$i]['assists'] = json_decode($gameDetailData)->info->participants[$i]->assists;
				$recap['participants'][$i]['totalMinionsKilled'] = json_decode($gameDetailData)->info->participants[$i]->totalMinionsKilled;
				$recap['participants'][$i]['item0'] = json_decode($gameDetailData)->info->participants[$i]->item0;
				$recap['participants'][$i]['item1'] = json_decode($gameDetailData)->info->participants[$i]->item1;
				$recap['participants'][$i]['item2'] = json_decode($gameDetailData)->info->participants[$i]->item2;
				$recap['participants'][$i]['item3'] = json_decode($gameDetailData)->info->participants[$i]->item3;
				$recap['participants'][$i]['item4'] = json_decode($gameDetailData)->info->participants[$i]->item4;
				$recap['participants'][$i]['item5'] = json_decode($gameDetailData)->info->participants[$i]->item5;
				$recap['participants'][$i]['item6'] = json_decode($gameDetailData)->info->participants[$i]->item6;
				$recap['participants'][$i]['visionScore'] = json_decode($gameDetailData)->info->participants[$i]->visionScore;

				forEach (json_decode($spells)->data as $_spell) { 
					if($_spell->key == json_decode($gameDetailData)->info->participants[$i]->summoner1Id) {
						$recap['participants'][$i]['summoner1Id'] = $_spell->id;
					}
					if($_spell->key == json_decode($gameDetailData)->info->participants[$i]->summoner2Id) {
						$recap['participants'][$i]['summoner2Id'] = $_spell->id;
					}
				}
				
				$recap['participants'][$i]['goldEarned'] = json_decode($gameDetailData)->info->participants[$i]->goldEarned;
				$recap['participants'][$i]['goldSpent'] = json_decode($gameDetailData)->info->participants[$i]->goldSpent;
			}

            $game = new Game();
            $game->setUuid($_game);
            $game->setTimeline(json_decode($gameTimeline, true));
			$game->setRecap($recap);
            $game->addPlayer($summoner);

            $manager->persist($game);
            
        }
        
        $manager->flush();
    }

    public function getApiKey(): string
    {
        return $this->container->get('app.api.key');
    }
}
