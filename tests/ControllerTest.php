<?php

namespace App\tests\testsApi;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ControllerTest extends KernelTestCase
{
    private $client;

    public function setUp(): void
    {
        $this->client = $this->getMockBuilder('Symfony\Contracts\HttpClient\HttpClientInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testApi()
    {
        $response = $this->client->request(
            'GET',
            'http://localhost:8000/api/player?name=qrab'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

}