<?php

namespace AS2\Tests\Unit;

use AS2\Tests\TestCase;

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

        $this->assertFalse($payload->isEncrypted());
        $this->assertTrue($payload->isCompressed());
        $this->assertEquals($senderId, $payload->getHeaderLine('as2-from'));
        $this->assertEquals($receiverId, $payload->getHeaderLine('as2-to'));
    }

    public function testProcessMessage()
    {
        // TODO
        $this->assertTrue(true);
    }

    public function testSendMessage()
    {
        // TODO
        $this->assertTrue(true);
    }
}
