<?php

/** @noinspection PhpUnused */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace AS2;

class Utils
{
    /**
     * @param  string  $content
     * @return string
     */
    public static function canonicalize($content)
    {
        return str_replace(["\r\n", "\r", "\n"], ["\n", "\n", "\r\n"], $content);
    }

    /**
     * @param  string  $mic
     * @return string
     */
    public static function normalizeMic($mic)
    {
        $parts = explode(',', $mic, 2);
        $parts[1] = strtolower(str_replace('-', '', $parts[1]));

        return implode(',', $parts);
    }

    /**
     * @param  string  $data
     * @return bool|string
     */
    public static function normalizeBase64($data)
    {
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if ((strlen($data) % 4 === 0) && preg_match(
                '/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/isU',
                $data
            )) {
            $decoded = base64_decode($data, true);
            if (base64_encode($decoded) === $data) {
                return $decoded;
            }
        }

        return $data;
    }

    /**
     * Parses an HTTP message into an associative array.
     *
     * The array contains the "headers" key containing an associative array of header
     * array values, and a "body" key containing the body of the message.
     *
     * @param  string  $message  HTTP request or response to parse.
     *
     * @return array
     */
    public static function parseMessage($message)
    {
        if (! $message) {
            throw new \InvalidArgumentException('Invalid message');
        }

        // TODO: refactory (RFC2231)
        $message = preg_replace("/; \r?\n\s/i", '; ', $message);
        // Iterate over each line in the message, accounting for line endings
        $lines = preg_split('/(\\r?\\n)/', $message, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = ['headers' => [], 'body' => ''];
        for ($i = 0, $totalLines = count($lines); $i < $totalLines; $i += 2) {
            $line = $lines[$i];
            // If two line breaks were encountered, then this is the end of body
            if (empty($line)) {
                if ($i < $totalLines - 1) {
                    $result['body'] = implode('', array_slice($lines, $i + 2));
                }
                break;
            }
            if (strpos($line, ':')) {
                $parts = explode(':', $line, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                $result['headers'][$key][] = $value;
            }
        }

        return $result;
    }

    /**
     * Parse an array of header values containing ";" separated data into an
     * array of associative arrays representing the header key value pair
     * data of the header. When a parameter does not contain a value, but just
     * contains a key, this function will inject a key with a '' string value.
     *
     * @param  string|array  $header  Header to parse into components.
     *
     * @return array Returns the parsed header values.
     */
    public static function parseHeader($header)
    {
        static $trimmed = "'\" \t\n\r\0\x0B";
        $params = [];
        foreach (self::normalizeHeader($header) as $val) {
            $part = [];
            foreach (preg_split('/;(?=([^"]*"[^"]*")*[^"]*$)/', $val) as $kvp) {
                $m = explode('=', $kvp, 2);
                if (isset($m[1])) {
                    $part[trim($m[0], $trimmed)] = trim($m[1], $trimmed);
                } else {
                    $part[] = trim($m[0], $trimmed);
                }
            }
            if ($part) {
                $params[] = $part;
            }
        }

        return $params;
    }

    /**
     * Converts an array of header values that may contain comma separated
     * headers into an array of headers with no comma separated values.
     *
     * @param  string|array  $header  Header to normalize.
     *
     * @return array Returns the normalized header field values.
     */
    public static function normalizeHeader($header)
    {
        if (! is_array($header)) {
            return array_map('trim', explode(',', $header));
        }
        $result = [];
        foreach ($header as $value) {
            foreach ((array) $value as $v) {
                if (strpos($v, ',') === false) {
                    $result[] = $v;
                    continue;
                }
                foreach (preg_split('/,(?=([^"]*"[^"]*")*[^"]*$)/', $v) as $vv) {
                    $result[] = trim($vv);
                }
            }
        }

        return $result;
    }

    /**
     * Converts an array of header values that may contain comma separated
     * headers into a string representation.
     *
     * @param  string[]  $headers
     * @param  string  $eol
     * @return string
     */
    public static function normalizeHeaders($headers, $eol = "\r\n")
    {
        $result = '';
        foreach ($headers as $name => $values) {
            $values = implode(', ', (array) $values);
            if ($name === 'Content-Type') {
                // some servers don't support "x-"
                $values = str_replace('x-pkcs7-', 'pkcs7-', $values);
            }
            $result .= $name.': '.$values.$eol;
        }

        return $result;
    }

    /**
     * Encode a given string in base64 encoding and break lines
     * according to the maximum line length.
     *
     * @param  string  $str
     * @param  int  $lineLength
     * @param  string  $lineEnd
     * @return string
     */
    public static function encodeBase64($str, $lineLength = 64, $lineEnd = "\r\n")
    {
        $lineLength -= ($lineLength % 4);

        return rtrim(chunk_split(base64_encode($str), $lineLength, $lineEnd));
    }

    /**
     * Generate Unique Message Id
     * TODO: uuid4
     *
     * @param  mixed  $partner
     * @return string
     */
    public static function generateMessageID($partner = null)
    {
        if ($partner instanceof PartnerInterface) {
            $partner = $partner->getAs2Id();
        }

        return date('Y-m-d')
            .'-'.
            uniqid('', true)
            .'@'.
            ($partner ? strtolower($partner).'.' : '')
            .
            str_replace(' ', '', php_uname('n'));
    }

    /**
     * Generate random string
     *
     * @param  int  $length
     * @param  string  $charList
     * @return string
     */
    public static function random($length = 10, $charList = '0-9a-z')
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $charList = count_chars(
            preg_replace_callback(
                '#.-.#',
                static function (array $m) {
                    return implode('', range($m[0][0], $m[0][2]));
                },
                $charList
            ),
            3
        );
        $chLen = strlen($charList);

        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be greater than zero.');
        }

        if ($chLen < 2) {
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
     * @param  string  $s
     * @return bool
     */
    public static function checkEncoding($s)
    {
        return $s === self::fixEncoding($s);
    }

    /**
     * Removes invalid code unit sequences from UTF-8 string
     *
     * @param  string  $s
     * @return bool
     */
    public static function fixEncoding($s)
    {
        // removes xD800-xDFFF, x110000 and higher
        return htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
    }
}
