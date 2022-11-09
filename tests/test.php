<?php

namespace App\tests;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Test extends TestCase
{
    public function testApi(HttpClientInterface $client)
    {
        $request = $client->request(
            'GET',
            'http://localhost:8000/api/players/qrab'
        );
        $response = $request->Send();
        $this->assertEquals(200, $response->getStatusCode());
    }

}