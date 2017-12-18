<?php

namespace AS2\Header;

use AS2\Headers;
use Zend\Mail\Exception;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\HeaderValue;
use Zend\Mail\Header\HeaderWrap;
use Zend\Mime\Mime;

class ContentType extends \Zend\Mail\Header\ContentType
{
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'content-type') {
            throw new Exception\InvalidArgumentException('Invalid header line for Content-Type string');
        }

        $value = str_replace(Headers::FOLDING, ' ', $value);
        $values = preg_split('#\s*;\s*#', $value);

        $type = array_shift($values);
        $header = new static();
        $header->setType($type);

        // Remove empty values
        $values = array_filter($values);

        foreach ($values as $keyValuePair) {
            list($key, $value) = explode('=', $keyValuePair, 2);
            $value = trim($value, "'\" \t\n\r\0\x0B");
            $header->addParameter($key, $value);
        }

        return $header;
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        $prepared = $this->type;
        if (empty($this->parameters)) {
            return $prepared;
        }

        $values = [$prepared];
        foreach ($this->parameters as $attribute => $value) {
            if (HeaderInterface::FORMAT_ENCODED === $format && !Mime::isPrintable($value)) {
                $this->encoding = 'UTF-8';
                $value = HeaderWrap::wrap($value, $this);
                $this->encoding = 'ASCII';
            }

            $values[] = sprintf('%s="%s"', $attribute, $value);
        }

        return implode(';' . Headers::FOLDING, $values);
    }
}
