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

        // loop les parties -> api -> get les timelines de chaque partie
        foreach ($jsonGames as $_game) {
            $gameDetailData = file_get_contents('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game, false, $context);
			$gameTimeline = file_get_contents('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game."/timeline", false, $context);

			// champs...
			// json_decode($gameDetailData)->info->gameDuration
			// game duration
            // game creation
            // game mode
            // Participants -> summonerName
            // Participants -> teamId
            // Participants -> championId
            // Participants -> champlevel
            // Participants -> role
            // Participants -> kills
            // Participants -> deaths
            // Participants -> assists
            // Participants -> totalMinionsKilled
            // Participants -> item0
            // Participants -> item1
            // Participants -> item2
            // Participants -> item3
            // Participants -> item4
            // Participants -> item5
            // Participants -> item6
            // Participants -> visonScore
            // Participants -> summoner2Id
            // Participants -> summoner1Id
            
			$recap = [];

			$recap['game_duration'] = json_decode($gameDetailData)->info->gameDuration;
			$recap['game_creation'] = json_decode($gameDetailData)->info->gameCreation;
			$recap['game_mode'] = json_decode($gameDetailData)->info->gameMode;

			forEach(json_decode($gameDetailData)->info->participants as $_player) {
				$recap['participants']['summonerName'] = $_player->summonerName;
				$recap['participants']['teamId'] = $_player->teamId;
				$recap['participants']['championId'] = $_player->championId;
				$recap['participants']['champlevel'] = $_player->champLevel;
				$recap['participants']['role'] = $_player->role;
				$recap['participants']['kills'] = $_player->kills;
				$recap['participants']['deaths'] = $_player->deaths;
				$recap['participants']['assists'] = $_player->assists;
				$recap['participants']['totalMinionsKilled'] = $_player->totalMinionsKilled;
				$recap['participants']['item0'] = $_player->item0;
				$recap['participants']['item1'] = $_player->item1;
				$recap['participants']['item2'] = $_player->item2;
				$recap['participants']['item3'] = $_player->item3;
				$recap['participants']['item4'] = $_player->item4;
				$recap['participants']['item5'] = $_player->item5;
				$recap['participants']['item6'] = $_player->item6;
				$recap['participants']['visionScore'] = $_player->visionScore;
				$recap['participants']['summoner2Id'] = $_player->summoner2Id;
				$recap['participants']['summoner1Id'] = $_player->summoner1Id;
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
