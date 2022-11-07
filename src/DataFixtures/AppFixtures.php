<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Services\RiotApiFetcher;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(private RiotApiFetcher $apiFetcher)
    {}
    
    public function load(ObjectManager $manager): void
    {
        $this->apiFetcher->populateOrUpdateDatabaseWithPlayer('qrab', $manager);
    }
}