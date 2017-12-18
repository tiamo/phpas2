<?php

namespace AS2\Tests;

use AS2\CryptoHelper;
use AS2\Management;
use AS2\MimePart;
use AS2\Server;
use AS2\StorageInterface;
use AS2\Tests\Mock\Logger;
use AS2\Tests\Mock\Storage;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var Management
     */
    protected $management;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function testPartner()
    {
        $partner = $this->storage->initPartner([
            'id' => 'amazon',
            'target_url' => 'http://127.0.0.1/as2/receive',
            'public_key' => file_get_contents( __DIR__ . '/resources/as2client.crt'),
            'private_key' => file_get_contents( __DIR__ . '/resources/as2client.key'),
            'private_key_pass_phrase' => 'password',
        ]);

        $this->assertTrue($this->storage->savePartner($partner));
    }

    public function testConstructor()
    {
//        $baseDir = __DIR__ . '/resources';
//        $filename = $baseDir . '/testmessage.edi';
//        $publicKey = $baseDir . '/as2client.crt';
//        $privateKey = $baseDir . '/as2client.key';
//
//        $public = file_get_contents($publicKey);
//        $private = file_get_contents($privateKey);
////
//        $message = new MimePart();
//        $message->setBody(file_get_contents($filename));
//        $message = CryptoHelper::compress($message);
//        $signedFile = CryptoHelper::sign($message, $public, [$private, 'password']);
//        $encryptedFile = CryptoHelper::encrypt($signedFile, $public);
//        $decryptedFile = CryptoHelper::decrypt($encryptedFile, $public, [$private, 'password']);
//
//        print_r($decryptedFile);
//        exit;

//
//        if ($decryptedFile->isSigned()) {
//            foreach ($decryptedFile->getParts() as $part) {
//                echo CryptoHelper::decompress($part);
//            }
//        }
//        exit;

//        $payload = MimePart::fromString();
//        $payload->setHeaders([
//        ]);
//        $message = $this->storage->newMessage();
//        $message = $this->management->buildMessage($message, $payload);


        $body = file_get_contents(__DIR__ . '/resources/message.txt');
        $payload = MimePart::fromString($body);

        $serverRequest = new ServerRequest(
            'POST',
            'http:://localhost',
            $payload->getHeaders()->toArray(),
            $payload->getBody(),
            '1.1',
            [
                'REMOTE_ADDR' => '127.0.0.1'
            ]
        );

        $response = $this->server->execute($serverRequest);

        var_dump($response->getStatusCode());
    }

    protected function setUp()
    {
        $this->logger = new Logger();
        $this->storage = new Storage();

        $this->management = new Management([]);
        $this->management->setLogger($this->logger);

        $this->server = new Server(
            $this->management,
            $this->storage,
            $this->logger
        );
    }
}