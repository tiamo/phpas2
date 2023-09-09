<?php

namespace AS2\Tests;

use AS2\Management;
use AS2\MessageRepositoryInterface;
use AS2\PartnerRepositoryInterface;
use AS2\Tests\Mock\MessageRepository;
use AS2\Tests\Mock\PartnerRepository;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Management
     */
    protected $management;

    /**
     * @var PartnerRepositoryInterface
     */
    protected $partnerRepository;

    /**
     * @var MessageRepositoryInterface
     */
    protected $messageRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerRepository = new PartnerRepository();
        $this->messageRepository = new MessageRepository();
        $this->management = new Management();
    }

    protected function loadFixture($name)
    {
        $res = file_get_contents(__DIR__.\DIRECTORY_SEPARATOR.'fixtures'.\DIRECTORY_SEPARATOR.$name);

        if ($res === false) {
            throw new \RuntimeException("Failed to load fixture $name");
        }

        return $res;
    }

    protected function getCerts($name = 'server')
    {
        $cert = $this->loadFixture($name.'.crt');
        $pkey = $this->loadFixture($name.'.pem');

        return [
            "cert" => $cert,
            "pkey" => $pkey,
        ];
    }

    protected function initMessage($file = 'test.edi')
    {
        $body = $this->loadFixture($file);

        return "Content-type: application/EDI-Consent\r\nContent-Transfer-Encoding: binary\r\nContent-Disposition: attachment; filename=payload.txt\r\n\r\n{$body}";
        // return new MimePart(
        //     [
        //         'Content-type' => 'application/EDI-Consent',
        //         'Content-Transfer-Encoding' => 'binary',
        //         'Content-Disposition' => 'attachment; filename=payload.txt',
        //     ], $this->loadFixture($file)
        // );
    }
}
