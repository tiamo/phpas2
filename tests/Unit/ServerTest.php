<?php

namespace AS2\Tests\Unit;

use AS2\MimePart;
use AS2\Server;
use AS2\Tests\TestCase;
use AS2\Utils;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * @internal
 *
 * @coversNothing
 */
final class ServerTest extends TestCase
{
    /**
     * @var Server
     */
    private $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = new Server(
            $this->management,
            $this->partnerRepository,
            $this->messageRepository
        );
    }

    public function testGet(): void
    {
        $response = $this->server->execute(new ServerRequest('GET', 'http:://localhost'));
        self::assertStringContainsString('To submit an AS2 message', $response->getBody()->getContents());
    }

    public function testSignedMdn(): void
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
        $body    = $response->getBody()->getContents();

        $mime = new MimePart($headers, $body);

        self::assertTrue($mime->isSigned());
        self::assertSame(2, $mime->getCountParts());

        $report = $mime->getPart(0);
        self::assertTrue($report->isReport());

        $content = $report->getPart(0);
        self::assertSame("Your message was successfully received and processed.\r\n", $content->getBodyString());
        self::assertSame('7bit', $content->getHeaderLine('Content-Transfer-Encoding'));

        $disposition = $report->getPart(1);
        self::assertStringContainsString('Original-Recipient: rfc822; B', $disposition->getBodyString());
        self::assertStringContainsString('Original-Recipient: rfc822; B', $disposition->getBodyString());
        self::assertStringContainsString('Final-Recipient: rfc822; B', $disposition->getBodyString());
        self::assertStringContainsString('Original-Message-ID: <test>', $disposition->getBodyString());
        self::assertStringContainsString(
            'Disposition: automatic-action/MDN-sent-automatically; processed',
            $disposition->getBodyString()
        );
        self::assertStringContainsString('oVDpnrSnpq+V99dXaarQ9HFyRUaFNsp9tdBBSmRhX4s=, sha256',
            $disposition->getBodyString());
    }
}
