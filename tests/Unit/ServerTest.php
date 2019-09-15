<?php

namespace AS2\Tests;

use AS2\CryptoHelper;
use AS2\Management;
use AS2\MimePart;
use AS2\PartnerInterface;
use AS2\Server;
use AS2\StorageInterface;
use AS2\Tests\Mock\ConsoleLogger;
use AS2\Tests\Mock\FileStorage;
use GuzzleHttp\Psr7\ServerRequest;

class ServerTest extends TestCase
{
    /**
     * @var Management
     */
    private $management;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var StorageInterface
     */
    private $storage;

    // public function testValidHeaders()
    // {
    //     $contents = $this->loadFixture('si_signed_cmp.msg');
    //     $payload = MimePart::fromString($contents);
    //
    //     $serverRequest = new ServerRequest(
    //         'POST',
    //         'http:://localhost',
    //         $payload->getHeaders(),
    //         $payload->getBody(),
    //         '1.1',
    //         [
    //             'REMOTE_ADDR' => '127.0.0.1',
    //         ]
    //     );
    //
    //     $response = $this->server->execute($serverRequest);
    //     $this->assertEquals(200, $response->getStatusCode());
    // }

    public function testBuildMessage()
    {
        $sender = $this->storage->initPartner(
            [
                'id' => 'A',
                'content_type' => 'application/EDI-Consent',
                'compression' => false,
                'signature_algorithm' => 'sha256',
                'encryption_algorithm' => '3des',
                'content_transfer_encoding' => 'binary',
                // 'mdn_mode' => 'sync',
                // 'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
            ]
        );

        $receiver = $this->storage->initPartner(
            [
                'id' => 'B',
                'content_type' => 'application/EDI-Consent',
                'compression' => true,
                'signature_algorithm' => 'sha256',
                'encryption_algorithm' => '3des',
                'content_transfer_encoding' => 'binary',
                // 'mdn_mode' => 'sync',
                // 'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
            ]
        );

        // initialize empty message
        $message = $this->storage->initMessage();
        $message->setMessageId('test');
        $message->setSender($sender);
        $message->setReceiver($receiver);

        $contents = $this->loadFixture('test.edi');

        // generate message payload
        $payload = $this->management->buildMessage($message, $contents);

        // CryptoHelper::decompress($payload);

        echo $payload;
        exit;
    }

    protected function setUp()
    {
        parent::setUp();

        $this->storage = new FileStorage();
        $this->management = new Management();
        $this->server = new Server($this->management, $this->storage);
    }

}
