<?php

namespace AS2\Tests\Unit;

use AS2\Tests\TestCase;
use AS2\CryptoHelper;
use AS2\MimePart;
use AS2\Utils;

class BaseTest extends TestCase
{
    // TODO: verify binary data
    // public function testVerify()
    // {
    //     $contents = $this->loadResource('si_signed.mdn');
    //     $payload = MimePart::fromString($contents);
    //
    //     $this->assertTrue($payload->isSigned());
    //
    //     $payload = CryptoHelper::verify($payload);
    //
    //     // print_r(openssl_error_string());
    //     // exit;
    //
    //     $this->assertTrue(empty(openssl_error_string()));
    // }

    public function testMicCalculation()
    {
        $contents = $this->loadFixture('mic-calculation');
        $this->assertEquals(
            "9IVZAN9QhjQINLzl/tdUvTMhMOSQ+96TjK7brHXQFys=, sha256",
            CryptoHelper::calculateMIC($contents, 'sha256')
        );
    }

    public function testSign()
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::sign($payload, $certs['cert'], $certs['pkey']);

        $this->assertTrue($payload->isSigned());

        $hasSignature = false;
        foreach ($payload->getParts() as $part) {
            if ($part->isPkc7Signature()) {
                $hasSignature = true;
                break;
            }
        }
        $this->assertTrue($hasSignature);
    }

    public function testEncrypt()
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::encrypt($payload, $certs['cert']);

        $this->assertTrue($payload->isEncrypted());
    }

    public function testDecrypt()
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::encrypt($payload, $certs['cert']);
        $payload = CryptoHelper::decrypt($payload, $certs['cert'], $certs['pkey']);

        $this->assertTrue($payload->getHeaderLine('content-type') == 'application/EDI-Consent');
    }

    public function testCompress()
    {
        $payload = $this->initMessage();
        $payload = CryptoHelper::compress($payload);

        $this->assertEquals(
            'compressed-data',
            $payload->getParsedHeader('content-type', 0, 'smime-type')
        );
        $body = base64_decode($payload->getBody());
        $this->assertEquals(0x30, ord($body[0]));
        $this->assertEquals(0x82, ord($body[1]));
        $this->assertEquals(0x02, ord($body[2]));
    }

    public function testDecompress()
    {
        $payload = MimePart::fromString($this->loadFixture('si_signed_cmp.msg'));

        $this->assertTrue($payload->isSigned());

        foreach ($payload->getParts() as $part) {
            if (! $part->isPkc7Signature()) {
                $payload = $part;
            }
        }

        $this->assertTrue($payload->isCompressed());

        $payload = CryptoHelper::decompress($payload);

        $this->assertEquals('Application/EDI-X12', $payload->getHeaderLine('content-type'));
        $this->assertTrue(strlen($payload->getBody()) == 2247);
    }

}
