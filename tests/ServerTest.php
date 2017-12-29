<?php

namespace AS2\Tests;

use AS2\CryptoHelper;
use AS2\Management;
use AS2\MimePart;
use AS2\PartnerInterface;
use AS2\Server;
use AS2\Tests\Mock\ConsoleLogger;
use AS2\Tests\Mock\FileStorage;
use AS2\Tests\Mock\Message;
use AS2\Tests\Mock\Partner;
use AS2\Utils;
use function GuzzleHttp\Psr7\_parse_message;
use function GuzzleHttp\Psr7\parse_request;
use function GuzzleHttp\Psr7\parse_response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;
use Zend\Mime\Mime;

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
        $result['to'] = $this->storage->initPartner([
            'id' => 'SN24EBMP2RGMIWU',
            'target_url' => 'http://as2.amazonsedi.com/1d1cdf88-8aa1-4fca-aad4-3c3a69b5fa80',
            'public_key' => file_get_contents($this->getResource('as2client.crt')),
//            'private_key' => file_get_contents($this->getResource('as2client.key')),
//            'private_key_pass_phrase' => 'password',
            'content_type' => 'application/edi-x12',
            'compression' => false,
            'sign' => true,
            'encrypt' => true,
            'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
            'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256'
        ]);
        $this->assertTrue($this->storage->savePartner($result['from']));
        $result['from'] = $this->storage->initPartner([
            'id' => 'TESLAAMAZING',
            'target_url' => 'https://teslaamazing.com/edi/as2/inbound',
            'public_key' => file_get_contents($this->getResource('teslaamazing.cer')),
            'private_key' => file_get_contents($this->getResource('teslaamazing.key')),
//            'private_key_pass_phrase' => 'password',
            'content_type' => 'application/edi-x12',
            'compression' => false,
            'sign' => true,
            'encrypt' => true,
            'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
            'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256'
        ]);
        $this->assertTrue($this->storage->savePartner($result['to']));
        return $result;
    }

//    /**
//     * @depends testInitPartners
//     * @param array $partners
//     * @inheritdoc
//     */
//    public function testReceiveMessage(array $partners)
//    {
//        $path = $this->getResource('edi_1514430043.txt');
////        $path = $this->getResource('edi_1514421486.txt');
//        $path = $this->getResource('test_binary.txt');
////        $path = $this->getResource('test.txt');
//        $content = file_get_contents($path);
//
//        $payload = Utils::parseMessage($content);
//
//        $serverRequest = new ServerRequest(
//            'POST',
//            'http://as2.amazonsedi.com',
//            $payload['headers'],
//            $payload['body'],
//            '1.1',
//            [
//                'REMOTE_ADDR' => '127.0.0.1'
//            ]
//        );
//        $response = $this->server->execute($serverRequest);
//        $this->assertEquals(200, $response->getStatusCode());
//    }

    /**
     * @depends testInitPartners
     * @param array $partners
     * @return \AS2\MessageInterface
     */
    public function testInitMessage(array $partners)
    {
        $message = $this->storage->initMessage([
            'id' => 'test',
        ]);
        $message->setSender($partners['from']);
        $message->setReceiver($partners['to']);

        $this->assertEquals($message->getMessageId(), 'test');
//        $this->assertEquals($message->getSender()->getAs2Id(), 'client');
//        $this->assertEquals($message->getReceiver()->getAs2Id(), 'server');
        return $message;
    }

    /**
     * @depends testInitMessage
     * @param Message $message
     * @return \AS2\MessageInterface
     */
    public function testBuildMessage(Message $message)
    {
        return $this->management->buildMessageFromFile(
            $message,
            $this->getResource('850_Sample.X12')
        );
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
            $payload->getHeaders(),
            $payload->getBody(),
            '1.1',
            [
                'REMOTE_ADDR' => '127.0.0.1'
            ]
        );
        $response = $this->server->execute($serverRequest);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testBuildMessage
     * @param Message $message
     * @return Message
     */
    public function testSendMdn(Message $message)
    {
        $this->assertNotEmpty($message->getSender());
        $this->assertNotEmpty($message->getReceiver());
        $this->assertNotEmpty($message->getHeaders());
        $this->assertNotEmpty($message->getPayload());

        $this->management->buildMdn($message, "The message sent to Recipient has been received");
        $this->management->sendMdn($message);
        $this->storage->saveMessage($message);

        return $message;
    }

    /**
     * @depends testSendMdn
     * @param Message $message
     */
    public function testReceiveMdn(Message $message)
    {
        $payload = MimePart::fromString($message->getMdnPayload());
        $serverRequest = new ServerRequest(
            'POST',
            'http:://localhost',
            $payload->getHeaders(),
            $payload->getBody(),
            '1.1',
            [
                'REMOTE_ADDR' => '127.0.0.1'
            ]
        );
        $response = $this->server->execute($serverRequest);
        $this->assertEquals(200, $response->getStatusCode());
    }

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