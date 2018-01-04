<?php

namespace AS2;

use phpseclib\File\ASN1;

class ASN1Helper
{
    const COMPRESSED_DATA_OID = '1.2.840.113549.1.9.16.1.9';
    const ZLIB_ALGORITHM_OID = '1.2.840.113549.1.9.16.3.8';
    const PKCS7_DATA_OID = '1.2.840.113549.1.7.1';

    const CONTENT_INFO_MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
            'content' => [
                'type' => ASN1::TYPE_ANY,
                'constant' => 0,
                'optional' => true,
                'explicit' => true
            ]
        ]
    ];

    const COMPRESSED_DATA_MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'version' => [
                'type' => ASN1::TYPE_INTEGER,
                'mapping' => ['0', '1', '2', '4', '5']
            ],
            'compression' => [
                'type' => ASN1::TYPE_SEQUENCE,
                'children' => [
                    'algorithm'  => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                    'parameters' => [
                        'type'     => ASN1::TYPE_ANY,
                        'optional' => true
                    ]
                ]
            ],
            'payload' => [
                'type' => ASN1::TYPE_SEQUENCE,
                'children' => [
                    'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                    'content' => [
                        'type' => ASN1::TYPE_OCTET_STRING,
                        'constant' => 0,
                        'optional' => true,
                        'explicit' => true
                    ]
                ]
            ]
        ]
    ];

    /**
     * @param string $data
     * @param array $map
     * @return array|bool|\phpseclib\File\ASN1\Element
     */
    public static function parse($data, $map)
    {
        $asn1 = new ASN1();
        $decoded = $asn1->decodeBER($data);
        if (empty($decoded)) {
            throw new \RuntimeException('Invalid ASN1 Data.');
        }
        return $asn1->asn1map($decoded[0], $map);
    }
}