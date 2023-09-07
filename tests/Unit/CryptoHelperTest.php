<?php

namespace AS2\Tests\Unit;

use AS2\CryptoHelper;
use AS2\MimePart;
use AS2\Tests\TestCase;

/**
 * @see CryptoHelper
 */
class CryptoHelperTest extends TestCase
{
    public function testMicCalculation()
    {
        $contents = $this->loadFixture('mic-calculation');
        self::assertEquals(
            '9IVZAN9QhjQINLzl/tdUvTMhMOSQ+96TjK7brHXQFys=, sha256',
            CryptoHelper::calculateMIC($contents, 'sha256')
        );
    }

    public function testSign()
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::sign($payload, $certs['cert'], $certs['pkey']);

        self::assertTrue($payload->isSigned());

        $hasSignature = false;
        foreach ($payload->getParts() as $part) {
            if ($part->isPkc7Signature()) {
                $hasSignature = true;
                break;
            }
        }
        self::assertTrue($hasSignature);
    }

    public function testVerifyBase64()
    {
        $contents = $this->loadFixture('signed-msg.txt');
        $payload = MimePart::fromString($contents);

        $certs = $this->getCerts();

        self::assertTrue($payload->isSigned());
        self::assertTrue(CryptoHelper::verify($payload, $certs['cert']));
    }

    public function testVerifyBinary()
    {
        $contents = $this->loadFixture('si_signed.mdn');
        $payload = MimePart::fromString($contents);

        $certs = $this->getCerts();

        self::assertTrue($payload->isSigned());
        self::assertTrue(CryptoHelper::verify($payload, $certs['cert']));
    }

    public function testEncrypt()
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::encrypt($payload, $certs['cert']);

        self::assertTrue($payload->isEncrypted());
    }

    public function testDecrypt()
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::encrypt($payload, $certs['cert']);
        $payload = CryptoHelper::decrypt($payload, $certs['cert'], $certs['pkey']);

        self::assertEquals('application/EDI-Consent', $payload->getHeaderLine('content-type'));
    }

    public function testCompress()
    {
        $payload = $this->initMessage();
        $payload = CryptoHelper::compress($payload);

        self::assertEquals(
            'compressed-data',
            $payload->getParsedHeader('content-type', 0, 'smime-type')
        );
        $body = base64_decode($payload->getBodyString());
        self::assertEquals(0x30, ord($body[0]));
        self::assertEquals(0x82, ord($body[1]));
        self::assertEquals(0x02, ord($body[2]));
    }

    public function testDecompress()
    {
        $payload = MimePart::fromString($this->loadFixture('si_signed_cmp.msg'));

        self::assertTrue($payload->isSigned());

        foreach ($payload->getParts() as $part) {
            if (! $part->isPkc7Signature()) {
                $payload = $part;
            }
        }

        self::assertTrue($payload->isCompressed());

        $payload = CryptoHelper::decompress($payload);

        self::assertEquals('Application/EDI-X12', $payload->getHeaderLine('content-type'));
        self::assertEquals(2247, strlen($payload->getBodyString()));
    }
}
