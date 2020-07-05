# DOCUMENTATION

Please have a look at an example application based on Slim3 framework.

You can also create your own classes.

- Implement MessageRepository class based on \AS2\MessageRepositoryInterface
- Implement Message class based on \AS2\MessageInterface
- Implement PartnerRepository class based on \AS2\PartnerRepositoryInterface
- Implement Partner class based on \AS2\PartnerInterface

### Example Receive AS2 Message
```php
$manager = new \AS2\Management();

/** @var /AS2/MessageRepositoryInterface $messageRepository */
$messageRepository = new MessageRepository();

/** @var /AS2/PartnerRepositoryInterface $partnerRepository */
$partnerRepository = new PartnerRepository();

$server = new \AS2\Server($manager, $partnerRepository, $messageRepository);

/** @var \GuzzleHttp\Psr7\Response $response */
$response = $server->excecute();
```

### Example Send AS2 Message
```php

$manager = new \AS2\Management();

/** @var /AS2/MessageRepositoryInterface $messageRepository */
$messageRepository = new MessageRepository();

/** @var /AS2/PartnerRepositoryInterface $partnerRepository */
$partnerRepository = new PartnerRepository();

// Init partners
$sender = $partnerRepository->findPartnerById('A');
$receiver = $partnerRepository->findPartnerById('B');

// Generate new message ID
$messageId = \AS2\Utils::generateMessageID($sender);
$rawMessage = '
Content-type: Application/EDI-X12
Content-disposition: attachment; filename=payload
Content-id: <test@test.com>

ISA*00~';

// Init new Message
$message = $messageRepository->createMessage();
$message->setMessageId($messageId);
$message->setSender($sender);
$message->setReceiver($receiver);

$payload = $manager->buildMessage($message, $rawMessage);
$manager->sendMessage($message, $payload);

$messageRepository->saveMessage($message);

```
