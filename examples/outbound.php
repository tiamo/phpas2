<?php

/** @noinspection ForgottenDebugOutputInspection */

require_once __DIR__."/bootstrap.php";

use AS2\Utils;

$senderId = 'mycompanyAS2';
$receiverId = 'mendelsontestAS2';

$rawMessage = <<<MSG
Content-type: Application/EDI-X12
content-disposition: attachment; filename=payload
content-id: <test@test.com>

ISA*00~
MSG;

// -----------------------------------------------------

$sender = $storage->findPartner($senderId);
$receiver = $storage->findPartner($receiverId);

$messageId = Utils::generateMessageID($senderId);

// Initialize New Message
$message = $storage->initMessage();
$message->setMessageId($messageId);
$message->setSender($sender);
$message->setReceiver($receiver);

// Generate Message Payload
$payload = $manager->buildMessage($message, $rawMessage);

// Try to send a message
if ($response = $manager->sendMessage($message, $payload)) {
    // echo MimePart::fromPsrMessage($response);
    echo 'OK';
}

$storage->saveMessage($message);
