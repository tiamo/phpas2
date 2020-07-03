<?php

namespace AS2\Tests;

use AS2\Server;
use AS2\Utils;
use GuzzleHttp\Psr7\ServerRequest;

class ServerTest extends TestCase
{
    /**
     * @var Server
     */
    private $server;

    public function testExecute()
    {
        $response = $this->server->execute(new ServerRequest('GET', 'http:://localhost'));
        $this->assertContains('To submit an AS2 message', $response->getBody()->getContents());

        // $message = $this->loadFixture('msg1.raw');
        // $payload = Utils::parseMessage($message);
        // $response = $this->server->execute(new ServerRequest(
        //     'POST',
        //     'http:://localhost',
        //     $payload['headers'],
        //     $payload['body'],
        //     '1.1',
        //     [
        //         'REMOTE_ADDR' => '127.0.0.1',
        //     ]
        // ));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->server = new Server(
            $this->management,
            $this->partnerRepository,
            $this->messageRepository
        );
    }
}
