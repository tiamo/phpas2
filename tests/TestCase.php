<?php

namespace AS2\Tests;

use AS2\MimePart;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function loadFixture($name)
    {
        return file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . $name);
    }

    protected function getCerts($file = 'phpas2.p12')
    {
        $certs  = [];
        $pkcs12 = $this->loadFixture($file);
        openssl_pkcs12_read($pkcs12, $certs, null);

        return $certs;
    }

    protected function initMessage($file = 'test.edi')
    {
        return new MimePart(
            [
                'Content-type'              => 'application/EDI-Consent',
                'Content-Transfer-Encoding' => 'binary',
                'Content-Disposition'       => 'attachment; filename=payload.txt',
            ], $this->loadFixture($file)
        );
    }
}
