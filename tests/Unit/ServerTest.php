<?php

namespace AS2\Tests\Unit;

use AS2\MimePart;
use AS2\Server;
use AS2\Tests\TestCase;
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
        self::assertContains('To submit an AS2 message', $response->getBody()->getContents());

        $message = $this->loadFixture('phpas2.raw');
        $payload = Utils::parseMessage($message);
        $response = $this->server->execute(new ServerRequest(
            'POST',
            'http:://localhost',
            $payload['headers'],
            $payload['body'],
            '1.1',
            [
                'REMOTE_ADDR' => '127.0.0.1',
            ]
        ));

        $mime = MimePart::fromString($response->getBody()->getContents());

        self::assertContains('multipart/report; report-type=disposition-notification',
            $mime->getHeaderLine('Content-Type'));
        self::assertEquals(2, $mime->getCountParts());

        $part0 = $mime->getPart(0);
        self::assertEquals("Your message was successfully received and processed.\r\n", $part0->getBody());
        self::assertEquals('7bit', $part0->getHeaderLine('Content-Transfer-Encoding'));

        $part1 = $mime->getPart(1);
        self::assertContains('Original-Recipient: rfc822; B', $part1->getBody());
        self::assertContains('Final-Recipient: rfc822; B', $part1->getBody());
        self::assertContains('Original-Message-ID: <test>', $part1->getBody());
        self::assertContains('Disposition: automatic-action/MDN-sent-automatically; processed', $part1->getBody());
        self::assertContains('oVDpnrSnpq+V99dXaarQ9HFyRUaFNsp9tdBBSmRhX4s=, sha256', $part1->getBody());
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
