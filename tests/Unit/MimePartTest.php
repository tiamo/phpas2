<?php

namespace AS2\Tests\Unit;

use AS2\MimePart;
use AS2\Tests\TestCase;

/**
 * @see MimePart
 */
class MimePartTest extends TestCase
{
    public function testIsEncrypted()
    {
        $cTypes = [
            'application/pkcs7-mime; name="smime.p7m"; smime-type=enveloped-data',
            'application/x-pkcs7-mime; name="smime.p7m"; smime-type=enveloped-data',
        ];

        foreach ($cTypes as $cType) {
            $mimePart = new MimePart(
                [
                    'Content-Type' => $cType,
                ]
            );
            self::assertTrue($mimePart->isPkc7Mime());
            self::assertTrue($mimePart->isEncrypted());
        }
    }

    public function testIsCompressed()
    {
        $cTypes = [
            'application/pkcs7-mime; name="smime.p7m"; smime-type=compressed-data',
            'application/x-pkcs7-mime; name="smime.p7m"; smime-type=compressed-data',
        ];

        foreach ($cTypes as $cType) {
            $mimePart = new MimePart(
                [
                    'Content-Type' => $cType,
                ]
            );
            self::assertTrue($mimePart->isCompressed());
        }
    }

    public function testIsSigned()
    {
        $mimePart = new MimePart(
            [
                'Content-Type' => 'multipart/signed;protocol="application/pkcs7-signature";micalg=sha1;boundary="_=A=_"',
            ]
        );
        self::assertTrue($mimePart->isSigned());
    }

    public function testIsPkc7Signature()
    {
        $mimePart = new MimePart(
            [
                'Content-Type' => 'Application/pkcs7-signature;name=EDIINTSIG.p7s',
            ]
        );
        self::assertTrue($mimePart->isPkc7Signature());
    }

    public function testIsReport()
    {
        $mimePart = new MimePart(
            [
                'Content-Type' => 'multipart/report;Report-Type=disposition-notification;',
            ]
        );
        self::assertTrue($mimePart->isReport());
    }

    public function testIsMultipart()
    {
        $boundary = '_=A=_';

        $mimePart = new MimePart(
            [
                'Content-Type' => 'multipart/mixed; boundary="'.$boundary.'"',
            ]
        );
        $mimePart->addPart('1');
        $mimePart->addPart('2');

        self::assertTrue($mimePart->isMultiPart());
        self::assertEquals($boundary, $mimePart->getParsedHeader('content-type', 0, 'boundary'));

        $mimePart = new MimePart(
            [
                'Content-Type' => 'multipart/mixed; boundary="'.$boundary.'"',
            ]
        );
        $mimePart->addPart('1');
        $mimePart->addPart('2');

        self::assertTrue($mimePart->isMultiPart());
        self::assertEquals($boundary, $mimePart->getParsedHeader('content-type', 0, 'boundary'));
    }

    public function testBody(): void
    {
        $mimePart = MimePart::fromString("content-type:text/plain;\n\ntest");
        self::assertEquals('test', $mimePart->getBodyString());

        $mimePart->setBody('test2');
        self::assertEquals('test2', $mimePart->getBodyString());

        $mimePart = MimePart::fromString("content-type:multipart/mixed;\r\n\r\ntest");
        self::assertEquals('test', $mimePart->getBodyString());

        $mimePart->setBody(new MimePart([], '1'));
        self::assertEquals(1, $mimePart->getCountParts());

        $mimePart->setBody(['2', '3']);
        self::assertEquals(3, $mimePart->getCountParts());
    }

    public function testMultipart(): void
    {
        $mime = MimePart::fromString($this->loadFixture('signed-msg.txt'));

        self::assertStringStartsWith('multipart/signed', $mime->getHeaderLine('content-type'));
        self::assertEquals(2, $mime->getCountParts());

        self::assertStringStartsWith('application/pkcs7-signature', $mime->getPart(1)->getHeaderLine('content-type'));
        self::assertEquals('application/EDI-Consent', $mime->getPart(0)->getHeaderLine('content-type'));
        self::assertEquals('binary', $mime->getPart(0)->getHeaderLine('Content-Transfer-Encoding'));
        self::assertStringStartsWith('UNB+UNOA', $mime->getPart(0)->getBodyString());
    }

    public function testBodyWithoutHeaders(): void
    {
        $res = MimePart::fromString($this->loadFixture('test.edi'));

        self::assertEmpty($res->getHeaders());
        self::assertStringStartsWith('UNB+UNOA', $res->getBodyString());
    }

    public function testCreateIfBinaryPartNotBinary(): void
    {
        $contents = $this->loadFixture('signed-msg.txt');
        $payload = MimePart::fromString($contents);

        self::assertNull(MimePart::createIfBinaryPart($payload));
    }

    public function testCreateIfBinaryPartBinary(): void
    {
        $contents = $this->loadFixture('si_signed.mdn');
        $payload = MimePart::fromString($contents);

        self::assertInstanceOf(MimePart::class, MimePart::createIfBinaryPart($payload));
    }
}
