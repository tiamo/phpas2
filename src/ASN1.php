<?php

namespace AS2;

class ASN1 extends \phpseclib\File\ASN1
{
    const PKCS_OID = '1.2.840.113549';
    const PKCS7_OID = self::PKCS_OID . '.1.7';
    const PKCS9_OID = self::PKCS_OID . '.1.9';
    const PKCS9_CT_OID = self::PKCS9_OID . '.16.1';

    const DATA_OID = self::PKCS7_OID . '.1';
    const SIGNED_DATA_OID = self::PKCS7_OID . '.2';
    const ENVELOPED_DATA_OID = self::PKCS7_OID . '.3';
    const SIGNED_AND_ENVELOPED_DATA_OID = self::PKCS7_OID . '.4';
    const DIGEST_DATA_OID = self::PKCS7_OID . '.5';
    const ENCRYPTED_DATA_OID = self::PKCS7_OID . '.6';

    const AUTHENTICATED_DATA_OID = self::PKCS9_CT_OID . '.2';
    const COMPRESSED_DATA_OID = self::PKCS9_CT_OID . '.9';
    const AUTH_ENVELOPED_DATA_OID = self::PKCS9_CT_OID . '.23';

    const ALG_3DES_OID = self::PKCS9_OID . '.16.3.6';
    const ALG_RC2_OID = self::PKCS9_OID . '.16.3.7';
    const ALG_ZLIB_OID = self::PKCS9_OID . '.16.3.8';
    const ALG_PWRI_KEK_OID = self::PKCS9_OID . '.16.3.9';

    const DIGEST_ALGORITHM_OID = self::PKCS_OID . '.2';
    const MD2_OID = self::DIGEST_ALGORITHM_OID . '.2';
    const MD4_OID = self::DIGEST_ALGORITHM_OID . '.4';
    const MD5_OID = self::DIGEST_ALGORITHM_OID . '.5';
    const HMAC_WITH_SHA1_OID = self::DIGEST_ALGORITHM_OID . '.7';

    const ENCRYPTION_ALGORITHM_OID = self::PKCS_OID . '.3';
    const RC2_CBC = self::ENCRYPTION_ALGORITHM_OID . '.2';
    const DES_EDE3_CBC = self::ENCRYPTION_ALGORITHM_OID . '.7';

    const ALGORITHM_IDENTIFIER_MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'algorithm' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
            'parameters' => [
                'type' => ASN1::TYPE_ANY,
                'optional' => true,
            ],
        ],
    ];

    const CONTENT_INFO_MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
            'content' => [
                'type' => ASN1::TYPE_OCTET_STRING,
                'constant' => 0,
                'optional' => true,
                'explicit' => true,
            ],
        ],
    ];

    const COMPRESSED_DATA_MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'version' => [
                'type' => ASN1::TYPE_INTEGER,
                'mapping' => ['0', '1', '2', '4', '5'],
            ],
            'compression' => self::ALGORITHM_IDENTIFIER_MAP,
            'payload' => self::CONTENT_INFO_MAP,
        ],
    ];

    /**
     * @param array|string $source
     * @param array $mapping
     * @param array $special
     * @return string
     */
    public static function encodeDER($source, $mapping, $special = [])
    {
        return parent::encodeDER($source, $mapping, $special);
    }

    /**
     * @param string $data
     * @param array $mapping
     * @return array
     */
    public static function decodeDER($data, $mapping)
    {
        $decoded = ASN1::decodeBER($data);
        if (empty($decoded)) {
            throw new \RuntimeException('Invalid ASN1 Data.');
        }

        return ASN1::asn1map($decoded[0], $mapping);
    }
}
