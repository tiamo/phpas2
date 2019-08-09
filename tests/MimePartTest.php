<?php
/**
 * Created by PhpStorm.
 * User: frjlros
 * Date: 09/08/19
 * Time: 13:24
 */

namespace AS2\Tests;


use AS2\CryptoHelper;
use AS2\MimePart;

class MimePartTest extends \PHPUnit_Framework_TestCase
{
    public function testMicCalculation()
    {
        $contents = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR."mic-calculation");
        $this->assertEquals(
            "rqrsInCnD6/dLvr5UO1ng0YGuqaG1wETIMTunzc77a0=, sha256",
            CryptoHelper::calculateMIC($contents, 'sha256')
        );
    }
}