<?php

namespace AS2;

use AS2\Mime;
use Zend\Mail\Header;

class MimePart
{
    const EOL = "\n";

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
     * @var Headers
     */
    protected $headers;

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
     * @param string $body
     * @param mixed $headers
     */
    public function __construct($body = null, $headers = null)
    {
        $this->setBody($body);
        $this->setHeaders($headers);
    }

    /**
     * Instantiate from raw message string
     *
     * @param  string $rawMessage
     * @return static
     */
    public static function fromString($rawMessage)
    {
        $message = new static();
        $headers = null;
        $body = null;

        Mime\Decode::splitMessage($rawMessage, $headers, $body);

        $message->setHeaders($headers);

        $contentType = $message->getContentType();
        if (strpos($contentType->getType(), 'multipart') === 0) {
            $boundary = $contentType->getParameter('boundary');
            $parts = Mime\Decode::splitMessageStruct($body, $boundary);
            if ($parts) {
                foreach ($parts as $part) {
                    $message->addPart($part);
                }
            }
        } else {
            $message->setBody($body);
        }

        return $message;
    }

    /**
     * @return bool
     */
    public function isPkc7Mime()
    {
        $type = $this->getContentType()->getType();
        return $type == self::TYPE_PKCS7_MIME || $type == self::TYPE_X_PKCS7_MIME;
    }

    /**
     * @return bool
     */
    public function isPkc7Signature()
    {
        $type = $this->getContentType()->getType();
        return $type == self::TYPE_PKCS7_SIGNATURE || $type == self::TYPE_X_PKCS7_SIGNATURE;
    }

    /**
     * @return bool
     */
    public function isEncrypted()
    {
        if ($this->isPkc7Mime()) {
            $smimeType = strtolower($this->getContentType()->getParameter('smime-type'));
            return $smimeType == self::SMIME_TYPE_ENCRYPTED;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        if ($this->isPkc7Mime()) {
            $smimeType = strtolower($this->getContentType()->getParameter('smime-type'));
            return $smimeType == self::SMIME_TYPE_COMPRESSED;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isSigned()
    {
        return strtolower($this->getContentType()->getType()) === self::MULTIPART_SIGNED;
    }

    /**
     * @return bool
     */
    public function isReport()
    {
        return strtolower($this->getContentType()->getType()) === self::MULTIPART_REPORT;
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
        } elseif (is_array($part) && isset($part['header']) && isset($part['body'])) {
            $message = new static();
            $message->setHeaders($part['header']);
            $message->setBody($part['body']);
            $this->parts[] = $message;
        } else {
            $this->parts[] = self::fromString((string)$part);
        }
        return $this;
    }

    /**
     * @param int $num
     */
    public function removePart($num)
    {
        if (isset($this->parts[$num])) {
            unset($this->parts[$num]);
        }
    }

    /**
     * Compose headers
     *
     * @param array|string|Headers $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        if ($headers instanceof Headers) {
            $this->headers = $headers;
        } elseif (is_array($headers)) {
            $this->headers = new Headers();
            foreach ($headers as $name => $value) {
                $this->headers->addHeaderLine($name, $value);
            }
        } else {
            $this->headers = Headers::fromString($headers);
        }
        return $this;
    }

    /**
     * Access headers collection
     *
     * Lazy-loads if not already attached.
     *
     * @return Headers
     */
    public function getHeaders()
    {
        if (null === $this->headers) {
            $this->setHeaders(new Headers());
        }
        return $this->headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return $this->getHeaders()->has($name);
    }

    /**
     * @param string $name
     * @return \ArrayIterator|bool|Header\HeaderInterface
     */
    public function getHeader($name)
    {
        return $this->getHeaders()->get($name);
    }

    /**
     * @param $name
     * @param null $value
     * @return $this
     */
    public function setHeader($name, $value = null)
    {
        $this->getHeaders()->removeHeader($name);
        return $this->addHeader($name, $value);
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addHeader($name, $value = null)
    {
        if ($name instanceof Header\HeaderInterface) {
            $this->getHeaders()->addHeader($name);
        } else {
            $this->getHeaders()->addHeaderLine($name, $value);
        }
        return $this;
    }

    /**
     * Return the currently set message body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string|static $body
     * @return $this
     */
    public function setBody($body)
    {
        if ($body instanceof static) {
            $this->addPart($body);
        } else {
            $this->body = $body;
        }
        return $this;
    }

    /**
     * @return Header\HeaderInterface|Header\ContentType
     */
    public function getContentType()
    {
        /** @var Header\ContentType $contentType */
        return $this->getHeaderByName('content-type', Header\ContentType::class);
    }

    /**
     * Serialize to string
     *
     * @return string
     */
    public function toString()
    {
        $headers = $this->getHeaders();
        $message = $headers->toString() . self::EOL . $this->getBody();
        if (count($this->parts) > 0) {
            $contentType = $this->getContentType();
            $boundary = $contentType->getParameter('boundary');
            if ($boundary) {
                $message .= self::EOL;
                foreach ($this->getParts() as $part) {
                    $message .= self::EOL;
                    $message .= '--' . $boundary . self::EOL;
                    $message .= $part->toString() . self::EOL;
                }
                $message .= '--' . $boundary . '--' . self::EOL;
            }
        }
        return $message;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Retrieve a header by name
     *
     * If not found, instantiates one based on $headerClass.
     *
     * @param  string $headerName
     * @param  string $headerClass
     * @return Header\HeaderInterface|\ArrayIterator header instance or collection of headers
     */
    protected function getHeaderByName($headerName, $headerClass)
    {
        $headers = $this->getHeaders();
        if ($headers->has($headerName)) {
            $header = $headers->get($headerName);
        } else {
            $header = new $headerClass();
            $headers->addHeader($header);
        }
        return $header;
    }
}