PHPAS2 is a php-based implementation of the EDIINT AS2 standard
====

The PHPAS2 application enables you to transmit and receive AS2 messages with 
EDI-X12, EDIFACT, XML, or binary payloads between trading partners.

[![Build Status](https://travis-ci.org/tiamo/phpas2.svg?branch=master)](https://travis-ci.org/tiamo/phpas2)

## Requirements

* php >= 5.5
* ext-openssl
* ext-zlib

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
Either run
```
composer require tiamo/phpas2
```
or add
```
"tiamo/phpas2": "^1.3"
```
to the require section of your `composer.json` file.

## Usage

* [Docs](./docs/index.md)
* [Examples](./examples)

Basic example
```bash
# start a server to receive messages in 8000 port
php -S localhost:8000 ./examples/server.php
```

```bash
# init partners and storage
php -f ./examples/init.php
```

```bash
# send message
php -f ./examples/outbound.php
```

## License

Licensed under the [MIT license](http://opensource.org/licenses/MIT).
