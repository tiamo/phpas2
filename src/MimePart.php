<?php

namespace AS2;

use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Utils as PsrUtils;
use Psr\Http\Message\MessageInterface as PsrMessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class MimePart implements PsrMessageInterface
{
    use MessageTrait;

    public const EOL = "\r\n";

    public const TYPE_PKCS7_MIME = 'application/pkcs7-mime';
    public const TYPE_X_PKCS7_MIME = 'application/x-pkcs7-mime';
    public const TYPE_PKCS7_SIGNATURE = 'application/pkcs7-signature';
    public const TYPE_X_PKCS7_SIGNATURE = 'application/x-pkcs7-signature';

    public const MULTIPART_SIGNED = 'multipart/signed';
    public const MULTIPART_REPORT = 'multipart/report';

    public const SMIME_TYPE_COMPRESSED = 'compressed-data';
    public const SMIME_TYPE_ENCRYPTED = 'enveloped-data';
    public const SMIME_TYPE_SIGNED = 'signed-data';

    public const ENCODING_7BIT = '7bit';
    public const ENCODING_8BIT = '8bit';
    public const ENCODING_QUOTEDPRINTABLE = 'quoted-printable';
    public const ENCODING_BASE64 = 'base64';

    /**
     * @var string
     */
    protected $rawMessage;

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
     *
     * @param  array  $headers
     * @param  string  $body
     * @param  string  $rawMessage
     */
    public function __construct($headers = [], $body = null, $rawMessage = null)
    {
        if ($rawMessage !== null) {
            $this->rawMessage = $rawMessage;
        }

        $this->setHeaders($this->normalizeHeaders($headers));

        if (! is_null($body)) {
            $this->setBody($body);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Instantiate from Request Object.
     *
     * @return static
     */
    public static function fromPsrMessage(PsrMessageInterface $message)
    {
        return new static($message->getHeaders(), $message->getBody()->getContents());
    }

    /**
     * Instantiate from Request Object.
     *
     * @return static
     *
     * @deprecated Please use MimePart::fromPsrMessage
     */
    public static function fromRequest(RequestInterface $request)
    {
        return self::fromPsrMessage($request);
    }

    /**
     * Instantiate from raw message string.
     *
     * @param  string  $rawMessage
     * @param  bool  $saveRaw
     *
     * @return static
     */
    public static function fromString($rawMessage, $saveRaw = true)
    {
        $payload = Utils::parseMessage($rawMessage);

        return new static($payload['headers'], $payload['body'], $saveRaw ? $rawMessage : null);
    }

    /**
     * Recreate message with base64 if part is binary.
     */
    public static function createIfBinaryPart(self $message): ?self
    {
        $hasBinary = false;

        $temp = new self($message->getHeaders());
        foreach ($message->getParts() as $part) {
            if (Utils::isBinary($part->getBodyString())) {
                $hasBinary = true;
                $recreatedPart = new self($part->getHeaders(), Utils::encodeBase64($part->getBodyString()));
                $temp->addPart($recreatedPart);
            } else {
                $temp->addPart($part);
            }
        }

        return $hasBinary ? $temp : null;
    }

    /**
     * @return bool
     */
    public function isPkc7Mime()
    {
        $type = $this->getParsedHeader('content-type', 0, 0);
        $type = strtolower($type);

        return $type === self::TYPE_PKCS7_MIME || $type === self::TYPE_X_PKCS7_MIME;
    }

    /**
     * @return bool
     */
    public function isPkc7Signature()
    {
        $type = $this->getParsedHeader('content-type', 0, 0);
        $type = strtolower($type);

        return $type === self::TYPE_PKCS7_SIGNATURE || $type === self::TYPE_X_PKCS7_SIGNATURE;
    }

    /**
     * @return bool
     */
    public function isEncrypted()
    {
        return $this->getParsedHeader('content-type', 0, 'smime-type') === self::SMIME_TYPE_ENCRYPTED;
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return $this->getParsedHeader('content-type', 0, 'smime-type') === self::SMIME_TYPE_COMPRESSED;
    }

    /**
     * @return bool
     */
    public function isSigned()
    {
        return $this->getParsedHeader('content-type', 0, 0) === self::MULTIPART_SIGNED;
    }

    /**
     * @return bool
     */
    public function isReport()
    {
        $isReport = $this->getParsedHeader('content-type', 0, 0) === self::MULTIPART_REPORT;

        if ($isReport) {
            return true;
        }

        if ($this->isSigned()) {
            foreach ($this->getParts() as $part) {
                if ($part->isReport()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isBinary()
    {
        return $this->getParsedHeader('content-transfer-encoding', 0, 0) === 'binary';
    }

    /**
     * @return bool
     */
    public function getCountParts()
    {
        return \count($this->parts);
    }

    /**
     * @return bool
     */
    public function isMultiPart()
    {
        return \count($this->parts) > 1;
    }

    /**
     * @return MimePart[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @return static|null
     */
    public function getPart($num)
    {
        return isset($this->parts[$num]) ? $this->parts[$num] : null;
    }

    /**
     * @return $this
     */
    public function addPart($part)
    {
        if ($part instanceof static) {
            $this->parts[] = $part;
        } else {
            $this->parts[] = self::fromString((string) $part);
        }

        return $this;
    }

    /**
     * @param  int  $num
     *
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
     * @param  string  $header
     * @param  int  $index
     * @param  int|string  $param
     *
     * @return array|string|null
     */
    public function getParsedHeader($header, $index = null, $param = null)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $header = Utils::parseHeader($this->getHeader($header));
        if ($index === null) {
            return $header;
        }
        $params = isset($header[$index]) ? $header[$index] : [];
        if ($param !== null) {
            return isset($params[$param]) ? $params[$param] : null;
        }

        return $params;
    }

    /**
     * Return the currently set message body.
     *
     * @return StreamInterface returns the body as a stream
     */
    public function getBody(): StreamInterface
    {
        $body = $this->body;
        if (\count($this->parts) > 0) {
            $boundary = $this->getParsedHeader('content-type', 0, 'boundary');
            if ($boundary) {
                // $body .= self::EOL;
                foreach ($this->getParts() as $part) {
                    // $body .= self::EOL;
                    $body .= '--'.$boundary.self::EOL;
                    $body .= $part->toString().self::EOL;
                }
                $body .= '--'.$boundary.'--'.self::EOL;
            }
        }

        return PsrUtils::streamFor($body);
    }

    /**
     * Return the currently set message body as a string.
     *
     * @return string returns the body as a string
     */
    public function getBodyString(): string
    {
        return PsrUtils::copyToString($this->getBody());
    }

    /**
     * @param  array|static|string  $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        if ($body instanceof static) {
            $this->addPart($body);
        } elseif (\is_array($body)) {
            foreach ($body as $part) {
                $this->addPart($part);
            }
        } else {
            $boundary = $this->getParsedHeader('content-type', 0, 'boundary');

            if ($boundary) {
                $parts = explode('--'.$boundary, $body);
                array_shift($parts); // remove unecessary first element
                array_pop($parts); // remove unecessary last element

                foreach ($parts as $part) {
                    // $part = preg_replace('/^\r?\n|\r?\n$/','',$part);
                    // Using substr instead of preg_replace as that option is removing multiple break lines instead of only one

                    // /^\r?\n/
                    if (str_starts_with($part, "\r\n")) {
                        $part = substr($part, 2);
                    } elseif ($part[0] === "\n") {
                        $part = substr($part, 1);
                    }
                    // /\r?\n$/
                    if (str_ends_with($part, "\r\n")) {
                        $part = substr($part, 0, -2);
                    } elseif (str_ends_with($part, "\n")) {
                        $part = substr($part, 0, -1);
                    }

                    $this->addPart($part);
                }
            } else {
                $this->body = $body;
            }
        }

        return $this;
    }

    /**
     * @return $this|self
     */
    public function withoutRaw()
    {
        $this->rawMessage = null;

        return $this;
    }

    /**
     * Serialize to string.
     *
     * @return string
     */
    public function toString()
    {
        if ($this->rawMessage) {
            return $this->rawMessage;
        }

        return $this->getHeaderLines().self::EOL.$this->getBodyString();
    }

    /**
     * @return array
     */
    private function normalizeHeaders($headers)
    {
        if (\is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'content-type') {
                    $headers[$key] = str_replace('x-pkcs7-', 'pkcs7-', $headers[$key]);
                }
            }
        }

        return $headers;
    }
}
