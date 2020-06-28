<?php

/** @noinspection PhpUnused */

namespace AS2;

use phpseclib\File\ASN1;

/**
 * TODO: new version of phpspeclib includes static methods "encodeDER", "decodeBER" ...
 */
class ASN1Helper extends ASN1
{
    const PKCS_OID     = '1.2.840.113549';
    const PKCS7_OID    = '1.2.840.113549.1.7';
    const PKCS9_OID    = '1.2.840.113549.1.9';
    const PKCS9_CT_OID = '1.2.840.113549.1.9.16.1';

    const DATA_OID                      = '1.2.840.113549.1.7.1';
    const SIGNED_DATA_OID               = '1.2.840.113549.1.7.2';
    const ENVELOPED_DATA_OID            = '1.2.840.113549.1.7.3';
    const SIGNED_AND_ENVELOPED_DATA_OID = '1.2.840.113549.1.7.4';
    const DIGEST_DATA_OID               = '1.2.840.113549.1.7.5';
    const ENCRYPTED_DATA_OID            = '1.2.840.113549.1.7.6';

    const AUTHENTICATED_DATA_OID  = '1.2.840.113549.1.9.16.1.2';
    const COMPRESSED_DATA_OID     = '1.2.840.113549.1.9.16.1.9';
    const AUTH_ENVELOPED_DATA_OID = '1.2.840.113549.1.9.16.1.23';

    const ALG_3DES_OID     = '1.2.840.113549.1.9.16.3.6';
    const ALG_RC2_OID      = '1.2.840.113549.1.9.16.3.7';
    const ALG_ZLIB_OID     = '1.2.840.113549.1.9.16.3.8';
    const ALG_PWRI_KEK_OID = '1.2.840.113549.1.9.16.3.9';

    const DIGEST_ALGORITHM_OID = '1.2.840.113549.2';
    const MD2_OID              = '1.2.840.113549.2.2';
    const MD4_OID              = '1.2.840.113549.2.4';
    const MD5_OID              = '1.2.840.113549.2.5';
    const HMAC_WITH_SHA1_OID   = '1.2.840.113549.2.7';

    const ENCRYPTION_ALGORITHM_OID = '1.2.840.113549.3';
    const RC2_CBC                  = '1.2.840.113549.3.2';
    const DES_EDE3_CBC             = '1.2.840.113549.3.7';

    /**
     * @return array
     */
    public static function getAlgorithmIdentifierMap()
    {
        return [
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => [
                'algorithm'  => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                'parameters' => [
                    'type'     => ASN1::TYPE_ANY,
                    'optional' => true,
                ],
            ],
        ];
    }

    /**
     * @param int $type
     *
     * @return array
     */
    public static function getContentInfoMap($type = ASN1::TYPE_ANY)
    {
        return [
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => [
                'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                'content'     => [
                    'type'     => $type,
                    'constant' => 0,
                    'optional' => true,
                    'explicit' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getCompressedDataMap()
    {
        return [
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => [
                'version' => [
                    'type'    => ASN1::TYPE_INTEGER,
                    'mapping' => ['0', '1', '2', '4', '5'],
                ],
                'compression' => self::getAlgorithmIdentifierMap(),
                'payload'     => [
                    'type'     => ASN1::TYPE_SEQUENCE,
                    'children' => [
                        'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                        'content'     => [
                            'type'     => ASN1::TYPE_OCTET_STRING,
                            'constant' => 0,
                            'explicit' => true,
                            'optional' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array|string $source
     * @param array|string $mapping
     * @param array        $filters
     *
     * @return string
     */
    public static function encode($source, $mapping, $filters = [])
    {
        $asn1 = new self();
        $asn1->loadFilters($filters);

        return $asn1->encodeDER($source, $mapping, $filters = []);
    }

    /**
     * @param string $data
     * @param array  $mapping
     *
     * @return array
     */
    public static function decode($data, $mapping = [])
    {
        $asn1    = new self();
        $decoded = $asn1->decodeBER($data);

        if (empty($decoded)) {
            throw new \RuntimeException('Invalid ASN1 Data.');
        }

        return $asn1->asn1map($decoded[0], $mapping);
    }
}
