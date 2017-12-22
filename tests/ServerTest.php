<?php

namespace AS2\Tests;

use AS2\Management;
use AS2\MimePart;
use AS2\PartnerInterface;
use AS2\Server;
use AS2\Tests\Mock\ConsoleLogger;
use AS2\Tests\Mock\FileStorage;
use AS2\Tests\Mock\Message;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;

/**
 * TODO: data providers
 */
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
     * @var FileStorage
     */
    protected $storage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return array
     */
    public function testInitPartners()
    {
        $result = [];
        $result['from'] = $this->storage->initPartner([
            'id' => 'client',
            'target_url' => 'http://127.0.0.1/as2/receive',
            'public_key' => file_get_contents($this->getResource('as2client.crt')),
            'private_key' => file_get_contents($this->getResource('as2client.key')),
            'private_key_pass_phrase' => 'password',
            'content_type' => 'application/edi-x12',
            'compression' => true,
            'sign' => true,
            'encrypt' => true,
            'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
            'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256'
        ]);
        $this->assertTrue($this->storage->savePartner($result['from']));
        $result['to'] = $this->storage->initPartner([
            'id' => 'server',
            'target_url' => 'http://127.0.0.1/as2/receive',
            'public_key' => file_get_contents($this->getResource('as2server.crt')),
            'private_key' => file_get_contents($this->getResource('as2server.key')),
            'private_key_pass_phrase' => 'password',
            'content_type' => 'application/edi-x12',
            'compression' => true,
            'sign' => true,
            'encrypt' => true,
            'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
            'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256'
        ]);
        $this->assertTrue($this->storage->savePartner($result['to']));
        return $result;
    }

    /**
     * @depends testInitPartners
     * @param array $partners
     * @return \AS2\MessageInterface
     */
    public function testInitMessage(array $partners)
    {
        $message = $this->storage->initMessage([
            'id' => 'test', // date('Ymd-His') . '-' . uniqid() . '@127.0.0.1',
        ]);
        $message->setSender($partners['from']);
        $message->setReceiver($partners['to']);

        return $message;
    }

    /**
     * @depends testInitMessage
     * @param Message $message
     * @return \AS2\MessageInterface
     */
    public function testBuildMessage(Message $message)
    {
        $file = $this->getResource('850_Sample.X12');
        $contentType = $message->getReceiver()->getContentType();
        $payload = new MimePart(file_get_contents($file), [
            'content-type' => $contentType ? $contentType : 'text/plain',
            'content-disposition' => 'attachment; filename="' . basename($file) . '"',
        ]);
        return $this->management->buildMessage($message, $payload);
    }

    /**
     * @depends testBuildMessage
     * @param Message $message
     * @return \AS2\MessageInterface
     */
    public function testSaveMessage(Message $message)
    {
        $this->assertNotEmpty($message->getMessageId());
        $this->assertNotEmpty($message->getSender());
        $this->assertNotEmpty($message->getReceiver());
        $this->assertNotEmpty($message->getHeaders());
        $this->assertNotEmpty($message->getPayload());

        $this->assertTrue($this->storage->saveMessage($message));

        return $message;
    }

    /**
     * @depends testSaveMessage
     * @param Message $message
     * @return \AS2\MessageInterface
     */
    public function testSendMessage(Message $message)
    {
        $this->assertNotEmpty($message->getSender());
        $this->assertNotEmpty($message->getReceiver());

        $this->management->sendMessage($message);
        $this->storage->saveMessage($message);

        return $message;
    }

    /**
     * @depends testSendMessage
     * @param Message $message
     */
    public function testReceiveMessage(Message $message)
    {
        $payload = MimePart::fromString($message->getHeaders() . MimePart::EOL . $message->getPayload());
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
        $this->assertEquals(200, $response->getStatusCode());
    }

//    /**
//     * @depends testBuildMessage
//     * @param Message $message
//     * @return Message
//     */
//    public function testSendMdn(Message $message)
//    {
//        $this->assertNotEmpty($message->getSender());
//        $this->assertNotEmpty($message->getReceiver());
//        $this->assertNotEmpty($message->getHeaders());
//        $this->assertNotEmpty($message->getPayload());
//
//        $this->management->buildMdn($message, "The message sent to Recipient has been received");
//        $this->management->sendMdn($message);
//        $this->storage->saveMessage($message);
//
//        return $message;
//    }
//
//    /**
//     * @depends testSendMdn
//     * @param Message $message
//     */
//    public function testReceiveMdn(Message $message)
//    {
//        $payload = MimePart::fromString($message->getMdnPayload());
//        $serverRequest = new ServerRequest(
//            'POST',
//            'http:://localhost',
//            $payload->getHeaders()->toArray(),
//            $payload->getBody(),
//            '1.1',
//            [
//                'REMOTE_ADDR' => '127.0.0.1'
//            ]
//        );
//        $response = $this->server->execute($serverRequest);
//        $this->assertEquals(200, $response->getStatusCode());
//    }

    /**
     * @param string $name
     * @return string
     */
    protected function getResource($name)
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $name;
    }

    protected function setUp()
    {
        $this->logger = new ConsoleLogger();
        $this->storage = new FileStorage();

        $this->management = new Management([]);
        $this->management->setLogger($this->logger);

        $this->server = new Server(
            $this->management,
            $this->storage,
            $this->logger
        );
    }
}