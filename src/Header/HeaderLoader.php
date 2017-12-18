<?php

namespace AS2\Header;

class HeaderLoader extends \Zend\Mail\Header\HeaderLoader
{
    /**
     * @var array Pre-aliased Header plugins
     */
    protected $plugins = [
        'contenttype' => ContentType::class,
        'content_type' => ContentType::class,
        'content-type' => ContentType::class,
        'contenttransferencoding' => ContentTransferEncoding::class,
        'content_transfer_encoding' => ContentTransferEncoding::class,
        'content-transfer-encoding' => ContentTransferEncoding::class,
        'message-id' => MessageId::class,
        'mimeversion' => MimeVersion::class,
        'mime_version' => MimeVersion::class,
        'mime-version' => MimeVersion::class,
    ];
}