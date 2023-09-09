<?php

namespace AS2\Tests\Unit;

use AS2\MessageInterface;
use AS2\MimePart;
use AS2\PartnerInterface;
use AS2\Tests\TestCase;
use AS2\Utils;

class ManagementTest extends TestCase
{
    public function testBuildMessage()
    {
        $senderId = 'A';
        $receiverId = 'B';

        $sender = $this->partnerRepository->findPartnerById($senderId);
        $receiver = $this->partnerRepository->findPartnerById($receiverId);

        // Initialize empty message
        $message = $this->messageRepository->createMessage();
        $message->setMessageId('test');
        $message->setSender($sender);
        $message->setReceiver($receiver);

        $contents = $this->loadFixture('test.edi');

        // generate message payload
        $payload = $this->management->buildMessage($message, $contents);

        self::assertTrue($payload->isEncrypted());
        self::assertEquals($senderId, $payload->getHeaderLine('as2-from'));
        self::assertEquals($receiverId, $payload->getHeaderLine('as2-to'));
    }

    public function testProcessMessage()
    {
        $payload = MimePart::fromString($this->loadFixture('phpas2.raw'));

        $sender = $this->partnerRepository->findPartnerById('A');
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

        self::assertTrue($message->getCompressed());
        self::assertTrue($message->getEncrypted());
        self::assertTrue($message->getSigned());
        self::assertSame($message->getMic(), 'oVDpnrSnpq+V99dXaarQ9HFyRUaFNsp9tdBBSmRhX4s=, sha256');
        self::assertSame(trim((string) $processedPayload), Utils::canonicalize($this->loadFixture('test.edi')));
    }

    public function testProcessMessageBiggerMessage()
    {
        $payload = MimePart::fromString($this->loadFixture('phpas2_new.raw'));

        $sender = $this->partnerRepository->findPartnerById('A');
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

        self::assertTrue($message->getEncrypted());
        self::assertTrue($message->getSigned());
        self::assertSame($message->getMic(), 'W/TUromzbAbSkSTwprjpnCWfT1Z3y96jikZyYKPzKXM=, sha256');
        self::assertSame((string) $processedPayload, Utils::canonicalize($this->loadFixture('test.xml')));
    }

    public function testSendMessage()
    {
        $senderId = 'A';
        $receiverId = 'B';

        $sender = $this->partnerRepository->findPartnerById($senderId);
        $receiver = $this->partnerRepository->findPartnerById($receiverId);

        $messageId = Utils::generateMessageID($sender);

        // Initialize empty message
        $message = $this->messageRepository->createMessage();
        $message->setMessageId($messageId);
        $message->setSender($sender);
        $message->setReceiver($receiver);

        self::assertEmpty($message->getStatus());

        $payload = $this->management->buildMessage($message, $this->loadFixture('test.edi'));

        self::assertSame(MessageInterface::STATUS_PENDING, $message->getStatus());

        $response = $this->management->sendMessage($message, $payload);

        self::assertFalse($response);
        self::assertSame(MessageInterface::STATUS_ERROR, $message->getStatus());
    }

    public function testBuildMdn()
    {
        $sender = $this->partnerRepository->findPartnerById('A');
        $receiver = $this->partnerRepository->findPartnerById('B');

        // Initialize empty message
        $message = $this->messageRepository->createMessage();
        $message->setMessageId('test');
        $message->setSender($sender);
        $message->setReceiver($receiver);

        $report = $this->management->buildMdn($message);

        self::assertEmpty($report->getBodyString());

        $message->setHeaders('disposition-notification-to: test@example.com');

        $report = $this->management->buildMdn($message, 'custom', 'error');

        self::assertTrue($report->isReport());
        self::assertEquals($report->getHeaderLine('as2-to'), $sender->getAs2Id());
        self::assertEquals($report->getHeaderLine('as2-from'), $receiver->getAs2Id());
        self::assertEquals('custom', trim($report->getPart(0)->getBodyString()));

        $headers = new MimePart(
            Utils::parseHeaders($report->getPart(1)->getBodyString())
        );

        self::assertEquals('<test>', $headers->getHeaderLine('Original-Message-ID'));
        self::assertEquals('rfc822; B', $headers->getHeaderLine('Original-Recipient'));
        self::assertEquals('rfc822; B', $headers->getHeaderLine('Final-Recipient'));
        self::assertEquals('automatic-action/MDN-sent-automatically; processed/error: error',
            $headers->getHeaderLine('Disposition'));

        self::assertEquals(MessageInterface::MDN_STATUS_SENT, $message->getMdnStatus());
        self::assertEquals(PartnerInterface::MDN_MODE_SYNC, $message->getMdnMode());

        // test signed

        $message->setHeaders("disposition-notification-to: test@example.com\ndisposition-notification-options: signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256");

        $report = $this->management->buildMdn($message, 'custom', 'error');

        self::assertTrue($report->isSigned());
    }

    // public function testSendMdn()
    // {
    //     $sender = $this->partnerRepository->findPartnerById('A');
    //     $sender->setTargetUrl('http://localhost');
    //
    //     $receiver = $this->partnerRepository->findPartnerById('B');
    //
    //     $messageId = Utils::generateMessageID($sender);
    //
    //     // Initialize empty message
    //     $message = $this->messageRepository->createMessage();
    //     $message->setMessageId($messageId);
    //     $message->setSender($sender);
    //     $message->setReceiver($receiver);
    //     $message->setMdnMode(PartnerInterface::MDN_MODE_ASYNC);
    //     $message->setMdnPayload($this->loadFixture('si_signed.mdn'));
    //
    //     $response = $this->management->sendMdn($message);
    //
    //     self::assertTrue(true);
    // }
}
