<?php

namespace AS2\Tests\Unit;

use AS2\CryptoHelper;
use AS2\MimePart;
use AS2\Tests\TestCase;

/**
 * @see CryptoHelper
 *
 * @internal
 *
 * @coversNothing
 */
final class CryptoHelperTest extends TestCase
{
    public function testMicCalculation(): void
    {
        $contents = $this->loadFixture('mic-calculation');
        self::assertSame(
            '9IVZAN9QhjQINLzl/tdUvTMhMOSQ+96TjK7brHXQFys=, sha256',
            CryptoHelper::calculateMIC($contents, 'sha256')
        );
    }

    public function testSign(): void
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

    public function testVerifyBase64(): void
    {
        $contents = $this->loadFixture('signed-msg.txt');
        $payload = MimePart::fromString($contents);

        $certs = $this->getCerts();

        self::assertTrue($payload->isSigned());
        self::assertTrue(CryptoHelper::verify($payload, $certs['cert']));
    }

    public function testVerifyBinary(): void
    {
        $contents = $this->loadFixture('si_signed.mdn');
        $payload = MimePart::fromString($contents);

        $certs = $this->getCerts();

        self::assertTrue($payload->isSigned());
        self::assertTrue(CryptoHelper::verify($payload, $certs['cert']));
    }

    public function testEncrypt(): void
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::encrypt($payload, $certs['cert']);

        self::assertTrue($payload->isEncrypted());
    }

    public function testDecrypt(): void
    {
        $payload = $this->initMessage();
        $certs = $this->getCerts();

        $payload = CryptoHelper::encrypt($payload, $certs['cert']);
        $payload = CryptoHelper::decrypt($payload, $certs['cert'], $certs['pkey']);

        self::assertSame('application/EDI-Consent', $payload->getHeaderLine('content-type'));
    }

    public function testCompress(): void
    {
        $payload = $this->initMessage();
        $payload = CryptoHelper::compress($payload);

        self::assertSame(
            'compressed-data',
            $payload->getParsedHeader('content-type', 0, 'smime-type')
        );
        $body = base64_decode($payload->getBodyString(), true);
        self::assertSame(0x30, \ord($body[0]));
        self::assertSame(0x82, \ord($body[1]));
        self::assertSame(0x02, \ord($body[2]));
    }

    public function testDecompress(): void
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

        self::assertSame('Application/EDI-X12', $payload->getHeaderLine('content-type'));
        self::assertSame(2247, \strlen($payload->getBodyString()));
    }
}
