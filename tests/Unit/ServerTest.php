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

    public function testGet()
    {
        $response = $this->server->execute(new ServerRequest('GET', 'http:://localhost'));
        self::assertStringContainsString('To submit an AS2 message', $response->getBody()->getContents());
    }

    public function testSignedMdn()
    {
        $message = $this->loadFixture('phpas2.raw');
        $payload = Utils::parseMessage($message);

        $response = $this->server->execute(
            new ServerRequest(
                'POST',
                'http:://localhost',
                $payload['headers'],
                $payload['body'],
                '1.1',
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                ]
            )
        );

        $headers = $response->getHeaders();
        $body = $response->getBody()->getContents();

        $mime = new MimePart($headers, $body);

        self::assertTrue($mime->isSigned());
        self::assertEquals(2, $mime->getCountParts());

        $report = $mime->getPart(0);
        self::assertTrue($report->isReport());

        $content = $report->getPart(0);
        self::assertEquals("Your message was successfully received and processed.\r\n", $content->getBody());
        self::assertEquals('7bit', $content->getHeaderLine('Content-Transfer-Encoding'));

        $disposition = $report->getPart(1);
        self::assertStringContainsString('Original-Recipient: rfc822; B', $disposition->getBody());
        self::assertStringContainsString('Original-Recipient: rfc822; B', $disposition->getBody());
        self::assertStringContainsString('Final-Recipient: rfc822; B', $disposition->getBody());
        self::assertStringContainsString('Original-Message-ID: <test>', $disposition->getBody());
        self::assertStringContainsString('Disposition: automatic-action/MDN-sent-automatically; processed',
            $disposition->getBody());
        self::assertStringContainsString('oVDpnrSnpq+V99dXaarQ9HFyRUaFNsp9tdBBSmRhX4s=, sha256', $disposition->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = new Server(
            $this->management,
            $this->partnerRepository,
            $this->messageRepository
        );
    }
}
