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
            $gameDetailData = file_get_contents('https://europe.api.riotgames.com/lol/match/v5/matches/'.$_game."/timeline", false, $context);

            $game = new Game();
            $game->setUuid($_game);
            $game->setTimeline(json_decode($gameDetailData, true));
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
