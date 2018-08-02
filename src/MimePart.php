<?php

namespace AS2;

use GuzzleHttp\Psr7\MessageTrait;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

class MimePart implements MessageInterface
{
    use MessageTrait;

    const EOL = "\r\n";

    const TYPE_PKCS7_MIME = 'application/pkcs7-mime';
    const TYPE_X_PKCS7_MIME = 'application/x-pkcs7-mime';
    const TYPE_PKCS7_SIGNATURE = 'application/pkcs7-signature';
    const TYPE_X_PKCS7_SIGNATURE = 'application/x-pkcs7-signature';

    const MULTIPART_SIGNED = 'multipart/signed';
    const MULTIPART_REPORT = 'multipart/report';

    const SMIME_TYPE_COMPRESSED = 'compressed-data';
    const SMIME_TYPE_ENCRYPTED = 'enveloped-data';
    const SMIME_TYPE_SIGNED = 'signed-data';

    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_QUOTEDPRINTABLE = 'quoted-printable';
    const ENCODING_BASE64 = 'base64';

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $parts = [];

    /**
     * MimePart constructor.
     * @param array $headers
     * @param null $body
     */
    public function __construct($headers = [], $body = null)
    {
        $this->setHeaders((array)$headers);
        if (!is_null($body)) {
            $this->setBody($body);
        }
    }

    /**
     * Instantiate from Request Object
     *
     * @param RequestInterface $request
     * @return static
     */
    public static function fromRequest(RequestInterface $request)
    {
        return new static($request->getHeaders(), $request->getBody()->getContents());
    }

    /**
     * Instantiate from raw message string
     *
     * @param  string $rawMessage
     * @return static
     */
    public static function fromString($rawMessage)
    {
        $payload = Utils::parseMessage($rawMessage);
        return new static($payload['headers'], $payload['body']);
    }

    /**
     * @return bool
     */
    public function isPkc7Mime()
    {
        $type = $this->getParsedHeader('content-type', 0, 0);
        return $type == self::TYPE_PKCS7_MIME || $type == self::TYPE_X_PKCS7_MIME;
    }

    /**
     * @return bool
     */
    public function isPkc7Signature()
    {
        $type = $this->getParsedHeader('content-type', 0, 0);
        return $type == self::TYPE_PKCS7_SIGNATURE || $type == self::TYPE_X_PKCS7_SIGNATURE;
    }

    /**
     * @return bool
     */
    public function isEncrypted()
    {
        return $this->getParsedHeader('content-type', 0, 'smime-type') == self::SMIME_TYPE_ENCRYPTED;
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return $this->getParsedHeader('content-type', 0, 'smime-type') == self::SMIME_TYPE_COMPRESSED;
    }

    /**
     * @return bool
     */
    public function isSigned()
    {
        return $this->getParsedHeader('content-type', 0, 0) == self::MULTIPART_SIGNED;
    }

    /**
     * @return bool
     */
    public function isReport()
    {
        return $this->getParsedHeader('content-type', 0, 0) == self::MULTIPART_REPORT;
    }

    /**
     * @return bool
     */
    public function isBinary()
    {
        return $this->getParsedHeader('content-transfer-encoding', 0, 0) == 'binary';
    }

    /**
     * @return bool
     */
    public function getCountParts()
    {
        return count($this->parts);
    }

    /**
     * @return bool
     */
    public function isMultiPart()
    {
        return (count($this->parts) > 1);
    }

    /**
     * @return MimePart[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @param $num
     * @return static|null
     */
    public function getPart($num)
    {
        return isset($this->parts[$num]) ? $this->parts[$num] : null;
    }

    /**
     * @param mixed $part
     * @return $this
     */
    public function addPart($part)
    {
        if ($part instanceof static) {
            $this->parts[] = $part;
        } else {
            $this->parts[] = self::fromString((string)$part);
        }
        return $this;
    }

    /**
     * @param int $num
     * @return bool
     */
    public function removePart($num)
    {
        if (isset($this->parts[$num])) {
            unset($this->parts[$num]);
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getHeaderLines()
    {
        return Utils::normalizeHeaders($this->headers, self::EOL);
    }

    /**
     * @param string $header
     * @param int $index
     * @param string|int $param
     * @return array|string|null
     */
    public function getParsedHeader($header, $index = null, $param = null)
    {
        $header = Utils::parseHeader($this->getHeader($header));
        if ($index === null) {
            return $header;
        }
        if (!isset($header[$index])) {
            return [];
        }
        if ($param !== null) {
            return isset($header[$index][$param]) ? $header[$index][$param] : null;
        }
        return $header[$index];
    }

    /**
     * Return the currently set message body
     *
     * @return string
     */
    public function getBody()
    {
        $body = $this->body;
        if (count($this->parts) > 0) {
            $boundary = $this->getParsedHeader('content-type', 0, 'boundary');
            if ($boundary) {
//                $body .= self::EOL;
                foreach ($this->getParts() as $part) {
//                    $body .= self::EOL;
                    $body .= '--' . $boundary . self::EOL;
                    $body .= $part->toString() . self::EOL;
                }
                $body .= '--' . $boundary . '--' . self::EOL;
            }
        }
        return $body;
    }

    /**
     * @param string|static $body
     * @return $this
     */
    public function setBody($body)
    {
        if ($body instanceof static) {
            $this->addPart($body);
        } elseif (is_array($body)) {
            foreach ($body as $part) {
                $this->addPart($part);
            }
        } else {
            $boundary = $this->getParsedHeader('content-type', 0, 'boundary');
            if ($boundary) {
                $separator = '--' . preg_quote($boundary, '/');
                // Get multi-part content
                if (preg_match('/' . $separator . '\r?\n(.+?)\r?\n' . $separator . '--/s', $body, $matches)) {
                    $parts = preg_split('/\r?\n' . $separator . '\r?\n/', $matches[1]);
                    foreach ($parts as $part) {
                        $this->addPart($part);
                    }
                }
            } else {
                $this->body = $body;
            }
        }
        return $this;
    }

    /**
     * Serialize to string
     *
     * @return string
     */
    public function toString()
    {
        return $this->getHeaderLines() . self::EOL . $this->getBody();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}