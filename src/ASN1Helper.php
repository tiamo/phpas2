<?php

namespace AS2;

use phpseclib3\File\ASN1;

class ASN1Helper
{
    public const OID_PKCS  = '1.2.840.113549';
    public const OID_PKCS7 = '1.2.840.113549.1.7';
    public const OID_PKCS9 = '1.2.840.113549.1.9';

    public const OID_PKCS9_CONTENT_TYPE       = '1.2.840.113549.1.9.3';
    public const OID_PKCS9_MESSAGE_DIGEST     = '1.2.840.113549.1.9.4';
    public const OID_PKCS9_SIGNING_TIME       = '1.2.840.113549.1.9.5';
    public const OID_PKCS9_SMIME_CAPABILITIES = '1.2.840.113549.1.9.15';

    public const OID_PKCS9_CT = '1.2.840.113549.1.9.16.1';

    public const OID_DATA                      = '1.2.840.113549.1.7.1';
    public const OID_SIGNED_DATA               = '1.2.840.113549.1.7.2';
    public const OID_ENVELOPED_DATA            = '1.2.840.113549.1.7.3';
    public const OID_SIGNED_AND_ENVELOPED_DATA = '1.2.840.113549.1.7.4';
    public const OID_DIGEST_DATA               = '1.2.840.113549.1.7.5';
    public const OID_ENCRYPTED_DATA            = '1.2.840.113549.1.7.6';

    public const OID_AUTHENTICATED_DATA  = '1.2.840.113549.1.9.16.1.2';
    public const OID_COMPRESSED_DATA     = '1.2.840.113549.1.9.16.1.9';
    public const OID_AUTH_ENVELOPED_DATA = '1.2.840.113549.1.9.16.1.23';

    public const OID_ALG_3DES     = '1.2.840.113549.1.9.16.3.6';
    public const OID_ALG_RC2      = '1.2.840.113549.1.9.16.3.7';
    public const OID_ALG_ZLIB     = '1.2.840.113549.1.9.16.3.8';
    public const OID_ALG_PWRI_KEK = '1.2.840.113549.1.9.16.3.9';

    public const OID_DIGEST_ALGORITHM     = '1.2.840.113549.2';
    public const OID_ENCRYPTION_ALGORITHM = '1.2.840.113549.3';

    // RSA encryption
    public const OID_RSA_ENCRYPTION = '1.2.840.113549.1.1.1';

    // RSA signature algorithms
    public const OID_MD2_WITH_RSA_ENCRYPTION    = '1.2.840.113549.1.1.2';
    public const OID_MD4_WITH_RSA_ENCRYPTION    = '1.2.840.113549.1.1.3';
    public const OID_MD5_WITH_RSA_ENCRYPTION    = '1.2.840.113549.1.1.4';
    public const OID_SHA1_WITH_RSA_ENCRYPTION   = '1.2.840.113549.1.1.5';
    public const OID_SHA256_WITH_RSA_ENCRYPTION = '1.2.840.113549.1.1.11';
    public const OID_SHA384_WITH_RSA_ENCRYPTION = '1.2.840.113549.1.1.12';
    public const OID_SHA512_WITH_RSA_ENCRYPTION = '1.2.840.113549.1.1.13';
    public const OID_SHA224_WITH_RSA_ENCRYPTION = '1.2.840.113549.1.1.14';

    // Elliptic Curve signature algorithms
    public const OID_ECDSA_WITH_SHA1   = '1.2.840.10045.4.1';
    public const OID_ECDSA_WITH_SHA224 = '1.2.840.10045.4.3.1';
    public const OID_ECDSA_WITH_SHA256 = '1.2.840.10045.4.3.2';
    public const OID_ECDSA_WITH_SHA384 = '1.2.840.10045.4.3.3';
    public const OID_ECDSA_WITH_SHA512 = '1.2.840.10045.4.3.4';

    // Elliptic Curve public key
    public const OID_EC_PUBLIC_KEY = '1.2.840.10045.2.1';

    // Cipher algorithms
    public const OID_DES_CBC      = '1.3.14.3.2.7';
    public const OID_RC2_CBC      = '1.2.840.113549.3.2';
    public const OID_DES_EDE3_CBC = '1.2.840.113549.3.7';
    public const OID_AES_128_CBC  = '2.16.840.1.101.3.4.1.2';
    public const OID_AES_192_CBC  = '2.16.840.1.101.3.4.1.22';
    public const OID_AES_256_CBC  = '2.16.840.1.101.3.4.1.42';

    // HMAC-SHA-1 from RFC 8018
    public const OID_HMAC_WITH_SHA1 = '1.2.840.113549.2.7';

    // HMAC algorithms from RFC 4231
    public const OID_HMAC_WITH_SHA224 = '1.2.840.113549.2.8';
    public const OID_HMAC_WITH_SHA256 = '1.2.840.113549.2.9';
    public const OID_HMAC_WITH_SHA384 = '1.2.840.113549.2.10';
    public const OID_HMAC_WITH_SHA512 = '1.2.840.113549.2.11';

    // Message digest algorithms
    public const OID_MD5    = '1.2.840.113549.2.5';
    public const OID_SHA1   = '1.3.14.3.2.26';
    public const OID_SHA224 = '2.16.840.1.101.3.4.2.4';
    public const OID_SHA256 = '2.16.840.1.101.3.4.2.1';
    public const OID_SHA384 = '2.16.840.1.101.3.4.2.2';
    public const OID_SHA512 = '2.16.840.1.101.3.4.2.3';

    public static function getContentInfoMap($type = ASN1::TYPE_ANY): array
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

    public static function getCompressedDataMap(): array
    {
        return [
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => [
                'version' => [
                    'type'    => ASN1::TYPE_INTEGER,
                    'mapping' => ['0', '1', '2', '4', '5'],
                ],
                'compression' => ASN1\Maps\AlgorithmIdentifier::MAP,
                'payload'     => self::getContentInfoMap(ASN1::TYPE_OCTET_STRING),
            ],
        ];
    }

    public static function certificateChoiceMap(): array
    {
        return [
            'type'     => ASN1::TYPE_CHOICE,
            'children' => [
                'certificate' => ASN1\Maps\Certificate::MAP,
                // 'extendedCertificate' => [], // Obsolete
                // 'v1AttrCert' => [], // Obsolete
                'v2AttrCert' => [
                    'type'     => ASN1::TYPE_SEQUENCE,
                    'implicit' => true,
                    'children' => [
                        'acinfo'             => ASN1::TYPE_ANY,
                        'signatureAlgorithm' => ASN1\Maps\AlgorithmIdentifier::MAP,
                        'signatureValue'     => ASN1::TYPE_BIT_STRING,
                    ],
                ],
                'other' => [
                    'type'     => ASN1::TYPE_SEQUENCE,
                    'implicit' => true,
                    'children' => [
                        'otherCertFormat' => ASN1::TYPE_OBJECT_IDENTIFIER,
                        'otherCert'       => ASN1::TYPE_ANY,
                    ],
                ],
            ],
        ];
    }

    public static function getSignerIdentifierMap(): array
    {
        return [
            'type'     => ASN1::TYPE_CHOICE,
            'children' => [
                'issuerAndSerialNumber' => [
                    'type'     => ASN1::TYPE_SEQUENCE,
                    'children' => [
                        'issuer'       => ASN1\Maps\Name::MAP,
                        'serialNumber' => ASN1\Maps\CertificateSerialNumber::MAP,
                    ],
                ],
                'subjectKeyIdentifier' => [
                    'type'     => ASN1::TYPE_OCTET_STRING,
                    'constant' => 0,
                    'implicit' => true,
                ],
            ],
        ];
    }

    public static function getSignerInfoMap(): array
    {
        return [
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => [
                'version' => [
                    'type'    => ASN1::TYPE_INTEGER,
                    'mapping' => ['0', '1', '2', '4', '5'],
                ],
                'sid'             => self::getSignerIdentifierMap(),
                'digestAlgorithm' => ASN1\Maps\AlgorithmIdentifier::MAP,
                'signedAttrs'     => ASN1\Maps\Attributes::MAP + [
                    'constant' => 0,
                    'optional' => true,
                    'implicit' => true,
                ],
                'signatureAlgorithm' => ASN1\Maps\AlgorithmIdentifier::MAP,
                'signature'          => ['type' => ASN1::TYPE_OCTET_STRING],
                // 'unsignedAttrs' => ASN1\Maps\Attributes::MAP + [
                //         'constant' => 1,
                //         'optional' => true,
                //         'implicit' => true,
                //     ],
            ],
        ];
    }

    public static function getSignedDataMap(): array
    {
        return [
            'type'     => ASN1::TYPE_SEQUENCE,
            'children' => [
                'contentType' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                'content'     => [
                    'type'     => ASN1::TYPE_SEQUENCE,
                    'constant' => 0,
                    'optional' => true,
                    'explicit' => true,
                    'children' => [
                        // CMSVersion
                        'version' => [
                            'type'    => ASN1::TYPE_INTEGER,
                            'mapping' => ['0', '1', '2', '4', '5'],
                        ],
                        'digestAlgorithms' => [
                            'type'     => ASN1::TYPE_SET,
                            'min'      => 1,
                            'max'      => -1,
                            'children' => ASN1\Maps\AlgorithmIdentifier::MAP,
                        ],
                        'contentInfo'  => self::getContentInfoMap(ASN1::TYPE_OCTET_STRING),
                        'certificates' => [
                            'type'     => ASN1::TYPE_SET,
                            'constant' => 0,
                            'implicit' => true,
                            'optional' => true,
                            'min'      => 1,
                            'max'      => -1,
                            // 'children' => self::certificateChoiceMap(),
                            'children' => ASN1\Maps\Certificate::MAP,
                        ],
                        'crls' => [
                            'type'     => ASN1::TYPE_SET,
                            'constant' => 1,
                            'implicit' => true,
                            'optional' => true,
                            'min'      => 1,
                            'max'      => -1,
                            'children' => ASN1\Maps\CertificateList::MAP,
                        ],
                        // 'a' => ['type' => ASN1::TYPE_ANY, 'optional' => true],
                        'signers' => [
                            'type'     => ASN1::TYPE_SET,
                            'min'      => 1,
                            'max'      => -1,
                            'children' => self::getSignerInfoMap(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array|string $source
     * @param array|string $mapping
     * @param array        $special
     *
     * @return string
     */
    public static function encode($source, $mapping, $filters = [], $special = [])
    {
        ASN1::setFilters($filters);

        return ASN1::encodeDER($source, $mapping, $special);
    }

    /**
     * @param string $data
     * @param array  $mapping
     *
     * @return array
     */
    public static function decode($data, $mapping = [])
    {
        $decoded = ASN1::decodeBER($data);

        if (empty($decoded)) {
            throw new \RuntimeException('Invalid ASN1 Data.');
        }

        return ASN1::asn1map($decoded[0], $mapping);
    }
}
