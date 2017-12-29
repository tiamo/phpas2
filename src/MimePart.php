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
        $this->setBody($body);
    }

    /**
     * @param RequestInterface $request
     * @param bool $forceBase64
     * @return static
     */
    public static function fromRequest(RequestInterface $request, $forceBase64 = true)
    {
        $body = $request->getBody()->getContents();
        if ($forceBase64) {
            $encoding = $request->getHeaderLine('content-transfer-encoding');
            if ($encoding == 'binary') {
                $request = $request->withHeader('Content-Transfer-Encoding', 'base64');
                $body = Utils::encodeBase64($body);
            }
        }
        return new static($request->getHeaders(), $body);
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
        if ($param !== null && isset($header[$index])) {
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
                $body .= self::EOL;
                foreach ($this->getParts() as $part) {
//                    $body .= self::EOL;
                    $body .= '--' . $boundary . self::EOL;
                    $body .= $part->toString() . self::EOL;
                }
                $body .= '--' . $boundary . '--';
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

//                // TODO: remove ?
//                $p = strpos($body, '--' . $boundary . "\n", 0);
//                $this->body = trim(substr($body, 0, $p));
//                $parts = Mime\Decode::splitMessageStruct($body, $boundary, self::EOL);
//                if ($parts) {
//                    foreach ($parts as $part) {
//                        $this->addPart($part);
//                    }
//                } else {
                $this->body = $body;
//                }
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
        return rtrim($this->getHeaderLines(), self::EOL) . self::EOL . $this->getBody();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}