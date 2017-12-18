<?php

namespace AS2;

class Utils
{
    /**
     * Generate random uuid string
     */
    public static function uuid1()
    {
        return '...';
    }

    /**
     * @param string $partner
     * @return string
     */
    public static function generateMessageID($partner)
    {
        $id = $partner instanceof PartnerInterface ? $partner->getUid() : 'unknown';
        return '<' . uniqid('', true) . '@' .
            round(microtime(true)) . '_' .
            str_replace(' ', '', strtolower($id) . '_' .
                php_uname('n')) . '>';
    }

    /**
     * Generate random string
     *
     * @param int $length
     * @param string $charList
     * @return string
     */
    public static function random(int $length = 10, string $charList = '0-9a-z'): string
    {
        $charList = count_chars(preg_replace_callback('#.-.#', function (array $m) {
            return implode('', range($m[0][0], $m[0][2]));
        }, $charList), 3);
        $chLen = strlen($charList);
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be greater than zero.');
        } elseif ($chLen < 2) {
            throw new \InvalidArgumentException('Character list must contain as least two chars.');
        }
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            $res .= $charList[mt_rand(0, $chLen - 1)];
        }
        return $res;
    }

    /**
     * Checks if the string is valid for UTF-8 encoding
     *
     * @param string $s
     * @return bool
     */
    public static function checkEncoding(string $s)
    {
        return $s === self::fixEncoding($s);
    }

    /**
     * Removes invalid code unit sequences from UTF-8 string
     *
     * @param string $s
     * @return bool
     */
    public static function fixEncoding(string $s)
    {
        // removes xD800-xDFFF, x110000 and higher
        return htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
    }
}