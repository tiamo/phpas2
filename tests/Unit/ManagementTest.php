<?php

namespace AS2\Tests\Unit;

use AS2\MessageInterface;
use AS2\MimePart;
use AS2\Tests\TestCase;
use AS2\Utils;

class ManagementTest extends TestCase
{
    public function testBuildMessage()
    {
        $senderId   = 'A';
        $receiverId = 'B';

        $sender   = $this->partnerRepository->findPartnerById($senderId);
        $receiver = $this->partnerRepository->findPartnerById($receiverId);

        // Initialize empty message
        $message = $this->messageRepository->createMessage();
        $message->setMessageId('test');
        $message->setSender($sender);
        $message->setReceiver($receiver);

        $contents = $this->loadFixture('test.edi');

        // generate message payload
        $payload = $this->management->buildMessage($message, $contents);

        $this->assertTrue($payload->isEncrypted());
        $this->assertEquals($senderId, $payload->getHeaderLine('as2-from'));
        $this->assertEquals($receiverId, $payload->getHeaderLine('as2-to'));
    }

    public function testProcessMessage()
    {
        $payload = MimePart::fromString($this->loadFixture('phpas2.raw'));

        $sender   = $this->partnerRepository->findPartnerById('A');
        $receiver = $this->partnerRepository->findPartnerById('B');

        $messageId = $payload->getHeaderLine('message-id');

        $message = $this->messageRepository->createMessage();
        $message->setMessageId($messageId);
        $message->setDirection(MessageInterface::DIR_INBOUND);
        $message->setStatus(MessageInterface::STATUS_IN_PROCESS);
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setHeaders($payload->getHeaderLines());

        $processedPayload = $this->management->processMessage($message, $payload);

        $this->assertTrue($message->getCompressed());
        $this->assertTrue($message->getEncrypted());
        $this->assertTrue($message->getSigned());
        $this->assertSame($message->getMic(), 'oVDpnrSnpq+V99dXaarQ9HFyRUaFNsp9tdBBSmRhX4s=, sha256');
        $this->assertSame((string) $processedPayload, Utils::canonicalize($this->loadFixture('test.edi')));
    }

    public function testSendMessage()
    {
        $senderId   = 'A';
        $receiverId = 'B';

        $sender   = $this->partnerRepository->findPartnerById($senderId);
        $receiver = $this->partnerRepository->findPartnerById($receiverId);

        $messageId = Utils::generateMessageID($sender);

        // Initialize empty message
        $message = $this->messageRepository->createMessage();
        $message->setMessageId($messageId);
        $message->setSender($sender);
        $message->setReceiver($receiver);

        $this->assertEmpty($message->getStatus());

        $payload = $this->management->buildMessage($message, $this->loadFixture('test.edi'));

        $this->assertSame(MessageInterface::STATUS_PENDING, $message->getStatus());

        $response = $this->management->sendMessage($message, $payload);

        $this->assertFalse($response);
        $this->assertSame(MessageInterface::STATUS_ERROR, $message->getStatus());
    }
}
