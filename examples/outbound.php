<?php

require_once "bootstrap.php";

$message = $storage->initMessage();
$message->setSender($storage->getPartner('phpas2'));
$message->setReceiver($storage->getPartner('mycompanyAS2'));
$message->setMessageId(\AS2\Utils::generateMessageID($message->getSender()));

$payload = new \AS2\MimePart([
    'Content-Type' => 'text/plain',
    'Content-Transfer-Encoding' => '7bit',
], 'test');

$manager->buildMessage($message, $payload);

$storage->saveMessage($message);

$response = $manager->sendMessage($message);

var_dump($response);
