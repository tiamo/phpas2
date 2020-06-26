# PHPAS2 is a php-based implementation of the EDIINT AS2 standard


[![Latest Version on Packagist](https://img.shields.io/packagist/v/tiamo/phpas2.svg?style=flat-square)](https://packagist.org/packages/tiamo/phpas2)
[![Build Status](https://travis-ci.org/tiamo/phpas2.svg?branch=master)](https://travis-ci.org/tiamo/phpas2)
[![Total Downloads](https://img.shields.io/packagist/dt/tiamo/phpas2.svg?style=flat-square)](https://packagist.org/packages/tiamo/phpas2)

The PHPAS2 application enables you to transmit and receive AS2 messages with 
EDI-X12, EDIFACT, XML, or binary payloads between trading partners.

## Requirements

* php >= 5.5
* ext-openssl
* ext-zlib

## Installation

```
composer require tiamo/phpas2
```

## Usage

* [Documentation](./docs/index.md)
* [Examples](./examples)

Basic example
```bash
# start a server to receive messages in 8000 port
php -S 127.0.0.1:8000 ./examples/server.php
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
