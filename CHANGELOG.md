# Change Log

## 2.0.1

* Add return types to MimePart, so it works on PHP 8 with the newer version of
  psr/http-message ([#23](https://github.com/tiamo/phpas2/pull/42))
* Improved tests
* Code refactored

## 2.0.0

* Minimum php version is 7.1
* Added support php 8.0
* Fixed bugs with mdn sign
* Added new message parser
* Improved tests
* Added v1 branch for 5.6 support
* Added github actions

## 1.4.8

* Fixed 3 bugs ([#28](https://github.com/tiamo/phpas2/pull/28))
* phpspeclib v3
* Improved tests

## 1.4.7

* Updated base64 decode on processMessage to fix problem on bigger
  mess… ([#23](https://github.com/tiamo/phpas2/pull/23))

## 1.4.6

* Improved usage of sample ([#19](https://github.com/tiamo/phpas2/pull/19))
* Fixed bad MIC compare ([#18](https://github.com/tiamo/phpas2/pull/18))
* Readme improved

## 1.4.5

* Fixed bug when MDN sent to own host ([#16](https://github.com/tiamo/phpas2/pull/16))
* Improved tests

## 1.4.4

* Fixed bug on Win OS ([#15](https://github.com/tiamo/phpas2/issues/15))

## 1.4.3

* Support guzzle: ^7.0 ([#14](https://github.com/tiamo/phpas2/issues/14))

## 1.4.0

* Bugfixes
* Minimum php >= 5.6
* Improved tests
* Removed StorageInterface
* Added PartnerRepositoryInterface
* Added MessageRepositoryInterface

## 1.3.8

* Fixed binary encoding bug
* Global code improvements

## 1.3.7

* Added header normalization
* Some code refactory

## 1.3.6

* psr/log@^1.1
* guzzlehttp/guzzle@^5.5
* Fixed exceptions
* Global code refactory
* Server: fixed MDN generation and error response
* Fixed MIC calculation
* Example improved

## 1.3.5

* Fix: Compression
* Fix: Mic calculation
* Code refactory
* Tests added
