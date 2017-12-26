PHPAS2 is a php-based implementation of the EDIINT AS2 standard
====

The PHPAS2 application enables you to transmit and receive AS2 messages with 
EDI-X12, EDIFACT, XML, or binary payloads between trading partners.

## Requirements

* PHP 5.4.0 and up.
* ext-openssl

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
Either run
```
php composer.phar require --prefer-dist tiamo/phpas2 "*"
```
or add
```
"tiamo/phpas2": "*"
```
to the require section of your `composer.json` file.

## Example Usage

- Implement Storage class based on \AS2\StorageInterface
- Implement Message class based on \AS2\MessageInterface
- Implement Partner class based on \AS2\PartnerInterface

### Example Receive AS2 Message
```php
$server = new \AS2\Server(new \AS2\Management(), new FileStorage());
/** @var \GuzzleHttp\Psr7\Response $response */
$response = $server->excecute();

```

### Example Send AS2 Message
```php

$manager = new \AS2\Management();
/** @var /AS2/StorageInterface $storage */
$storage = new FileStorage();

// Init new Message
$message = $storage->initMessage(['id' => 'test' ...]);

// Init sending Partner
$sender = $storage->initPartner([
    'id' => 'partner_a',
    'target_url' => 'http://127.0.0.1/as2/receive',
    'public_key' => file_get_contents('public_a.crt'),
    'private_key' => file_get_contents('private_a.key'),
    'private_key_pass_phrase' => 'password',
    'content_type' => 'application/edi-x12',
    'compression' => true,
    'sign' => true,
    'encrypt' => true,
    'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
    'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256'
]);

$message->setSender($sender);

// Init receiving Partner
$receiver = $storage->initPartner([
    'id' => 'partner_b',
    'target_url' => 'http://127.0.0.1/as2/receive',
    'public_key' => file_get_contents('public_b.crt'),
    'private_key' => file_get_contents('private_b.key'),
    'private_key_pass_phrase' => 'password',
    'content_type' => 'application/edi-x12',
    'compression' => true,
    'sign' => true,
    'encrypt' => true,
    'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
    'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256'
]);

$message->setReceiver($receiver);

$manager->buildMessage($message);

$manager->sendMessage($message);

$storage->saveMessage($message);

```

## License

Licensed under the [MIT license](http://opensource.org/licenses/MIT).
