<?php

namespace AS2;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\File\X509;

/**
 * TODO: Implement pure methods without "openssl_pkcs7"
 * check openssl_pkcs7 doesn't work with binary data.
 */
class CryptoHelper
{
    /**
     * Extract the message integrity check (MIC) from the digital signature.
     *
     * @param  MimePart|string  $payload
     * @param  string  $algo  Default is SHA256
     * @param  bool  $includeHeaders
     *
     * @return string
     */
    public static function calculateMIC($payload, $algo = 'sha256', $includeHeaders = true)
    {
        $digestAlgorithm = str_replace('-', '', strtolower($algo));

        if (! in_array($digestAlgorithm, hash_algos(), true)) {
            throw new \InvalidArgumentException(sprintf('(MIC) Invalid hash algorithm `%s`.', $digestAlgorithm));
        }

        if (! ($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        $digest = base64_encode(
            hash(
                $digestAlgorithm,
                $includeHeaders ? $payload : $payload->getBody(),
                true
            )
        );

        return $digest.', '.$algo;
    }

    /**
     * Sign data which contains mime headers.
     *
     * @param  string|MimePart  $data
     * @param  string  $publicKey
     * @param  string|array  $privateKey
     * @param  array  $headers
     * @param  array  $micAlgo
     *
     * @return MimePart
     */
    public static function signPure($data, $publicKey, $privateKey = null, $headers = [], $micAlgo = null)
    {
        if (! is_array($privateKey)) {
            $privateKey = [$privateKey, false];
        }

        $singAlg = 'sha256';

        /** @var RSA\PrivateKey $private */
        $private = RSA::load($privateKey[0], $privateKey[1])
            ->withPadding(RSA::SIGNATURE_PKCS1)
            ->withHash($singAlg)
            ->withMGFHash($singAlg);

        $signature = $private->sign($data);

        $certInfo = self::loadX509($publicKey);

        $digestAlgorithm = ASN1Helper::OID_SHA256;

        $payload = ASN1Helper::encode(
            [
                'contentType' => ASN1Helper::OID_SIGNED_DATA,
                'content' => [
                    'version' => 1,
                    'digestAlgorithms' => [
                        [
                            'algorithm' => $digestAlgorithm,
                        ],
                    ],
                    'contentInfo' => [
                        'contentType' => ASN1Helper::OID_DATA,
                    ],
                    'certificates' => [
                        $certInfo,
                    ],
                    // 'crls' => [],
                    'signers' => [
                        [
                            'version' => '1',
                            'sid' => [
                                'issuerAndSerialNumber' => [
                                    'issuer' => $certInfo['tbsCertificate']['issuer'],
                                    'serialNumber' => $certInfo['tbsCertificate']['serialNumber'],
                                ],
                            ],
                            'digestAlgorithm' => [
                                'algorithm' => $digestAlgorithm,
                            ],
                            'signedAttrs' => [
                                [
                                    'type' => ASN1Helper::OID_PKCS9_CONTENT_TYPE,
                                    'value' => [
                                        [
                                            'objectIdentifier' => ASN1Helper::OID_DATA,
                                        ],
                                    ],
                                ],
                                [
                                    'type' => ASN1Helper::OID_PKCS9_SIGNING_TIME,
                                    'value' => [
                                        [
                                            // TODO: Fri, 26 Jun 2020 14:47:26 +0000
                                            'utcTime' => date('c'),
                                        ],
                                    ],
                                ],
                                // [
                                //     'type' => ASN1Helper::OID_PKCS9_MESSAGE_DIGEST,
                                //     'value' => [
                                //         [
                                //             'octetString' => "",
                                //         ],
                                //     ],
                                // ],
                            ],
                            'signatureAlgorithm' => [
                                'algorithm' => ASN1Helper::OID_RSA_ENCRYPTION,
                            ],
                            'signature' => $signature,
                            // 'unsignedAttrs' => []
                        ],
                    ],
                ],
            ],
            ASN1Helper::getSignedDataMap()
        );

        $payload = Utils::encodeBase64($payload);

        $signatureMime = new MimePart([
            'Content-Transfer-Encoding' => 'base64',
            'Content-Disposition' => 'attachment; filename="smime.p7s"',
            'Content-Type' => 'application/pkcs7-signature; name=smime.p7s; smime-type=signed-data',
        ], $payload);

        $boundary = '=_'.sha1(uniqid('', true));

        $result = new MimePart([
                'MIME-Version' => '1.0',
                'Content-type' => 'multipart/signed; protocol="application/pkcs7-signature"; micalg='.$singAlg.'; boundary="----'.$boundary.'"',
            ] + $headers);
        $result->addPart($data);
        $result->addPart($signatureMime);

        // echo $result;
        // exit;

        return $result;
    }

    /**
     * TODO: extra certs
     *
     * @param  string|MimePart  $payload
     * @param  array|null  $caInfo  Information about the trusted CA certificates to use in the verification process
     * @param  array  $rootCerts
     *
     * @return bool
     */
    public static function verifyPure($payload, $publicKey, $extraCerts = []): bool
    {
        if (is_string($payload)) {
            $payload = MimePart::fromString($payload);
        }

        $data = "";
        $signature = false;

        foreach ($payload->getParts() as $part) {
            if ($part->isPkc7Signature()) {
                $signature = $part->getBody();
            } else {
                $data = $part->toString();
            }
        }

        if (! $signature) {
            return false;
        }

        $verified = true;

        $signedData = ASN1Helper::decode(Utils::normalizeBase64($signature), ASN1Helper::getSignedDataMap());
        if ($signedData['contentType'] === ASN1Helper::OID_SIGNED_DATA) {
            /** @var RSA\PublicKey $public */
            $public = PublicKeyLoader::load($publicKey)->withPadding(RSA::SIGNATURE_PKCS1);
            foreach ($signedData['content']['signers'] as $signer) {
                $verified &= $public->verify($data, $signer['signature']);
            }
        }

        return (bool) $verified;
    }

    /**
     * Sign data which contains mime headers.
     *
     * @param  string|MimePart  $data
     * @param  string|resource  $cert
     * @param  string|array  $privateKey
     * @param  array  $headers
     * @param  array  $micAlgo
     *
     * @return MimePart
     */
    public static function sign($data, $cert, $privateKey = null, $headers = [], $micAlgo = null)
    {
        $data = self::getTempFilename($data."\r\n");
        $temp = self::getTempFilename();

        $flags = PKCS7_DETACHED;

        if (! openssl_pkcs7_sign($data, $temp, $cert, $privateKey, $headers, $flags)) {
            throw new \RuntimeException(sprintf('Failed to sign S/Mime message. Error: "%s".', openssl_error_string()));
        }

        $payload = MimePart::fromString(file_get_contents($temp), false);

        if ($micAlgo) {
            $contentType = $payload->getHeaderLine('content-type');
            $contentType = preg_replace('/micalg=(.+);/i', 'micalg="'.$micAlgo.'";', $contentType);
            /** @var MimePart $payload */
            $payload = $payload->withHeader('Content-Type', $contentType);
        }

        // replace x-pkcs7-signature > pkcs7-signature
        foreach ($payload->getParts() as $key => $part) {
            if ($part->isPkc7Signature()) {
                $payload->removePart($key);
                $payload->addPart(
                    $part->withoutRaw()->withHeader(
                        'Content-Type',
                        'application/pkcs7-signature; name=smime.p7s; smime-type=signed-data'
                    )
                );
            }
        }

        return $payload;
    }

    /**
     * @param  string|MimePart  $data
     * @param  array|null  $caInfo  Information about the trusted CA certificates to use in the verification process
     * @param  array  $rootCerts
     *
     * @return bool
     */
    public static function verify($data, $caInfo = null, $rootCerts = [])
    {
        if ($data instanceof MimePart) {
            $temp = MimePart::createIfBinaryPart($data);
            if ($temp !== null) {
                $data = $temp;
            }

            $data = self::getTempFilename((string) $data);
        }

        if (! empty($caInfo)) {
            foreach ((array) $caInfo as $cert) {
                $rootCerts[] = self::getTempFilename($cert);
            }
        }

        $flags = PKCS7_BINARY | PKCS7_NOSIGS;

        // if (empty($rootCerts)) {
        $flags |= PKCS7_NOVERIFY;
        // }

        $outFile = self::getTempFilename();

        $res = openssl_pkcs7_verify($data, $flags, $outFile, $rootCerts) === true;

        dd([
            $res,
            openssl_error_string(),
        ]);

        return $res;
    }

    /**
     * @param  string|MimePart  $data
     * @param  string|array  $cert
     * @param  int|string  $cipher
     *
     * @return MimePart
     */
    public static function encrypt($data, $cert, $cipher = OPENSSL_CIPHER_AES_128_CBC)
    {
        $data = self::getTempFilename((string) $data);

        if (is_string($cipher)) {
            $cipher = strtoupper($cipher);
            $cipher = \str_replace('-', '_', $cipher);
            if (defined('OPENSSL_CIPHER_'.$cipher)) {
                $cipher = constant('OPENSSL_CIPHER_'.$cipher);
            }
        }

        $temp = self::getTempFilename();
        if (! openssl_pkcs7_encrypt($data, $temp, $cert, [], PKCS7_BINARY, $cipher)) {
            throw new \RuntimeException(sprintf('Failed to encrypt S/Mime message. Error: "%s".',
                openssl_error_string()));
        }

        return MimePart::fromString(file_get_contents($temp), false);
    }

    /**
     * @param  string|MimePart  $data
     * @param  mixed  $cert
     * @param  mixed  $key
     *
     * @return MimePart
     */
    public static function decrypt($data, $cert, $key = null)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string) $data);
        }

        $temp = self::getTempFilename();
        if (! openssl_pkcs7_decrypt($data, $temp, $cert, $key)) {
            throw new \RuntimeException(sprintf('Failed to decrypt S/Mime message. Error: "%s".',
                openssl_error_string()));
        }

        return MimePart::fromString(file_get_contents($temp));
    }

    /**
     * Compress data.
     *
     * @param  string|MimePart  $data
     * @param  string  $encoding
     *
     * @return MimePart
     */
    public static function compress($data, $encoding = null)
    {
        if ($data instanceof MimePart) {
            $content = $data->toString();
        } else {
            $content = is_file($data) ? file_get_contents($data) : $data;
        }

        if (empty($encoding)) {
            $encoding = MimePart::ENCODING_BASE64;
        }

        $headers = [
            'Content-Type' => MimePart::TYPE_PKCS7_MIME.'; name="smime.p7z"; smime-type='.MimePart::SMIME_TYPE_COMPRESSED,
            'Content-Description' => 'S/MIME Compressed Message',
            'Content-Disposition' => 'attachment; filename="smime.p7z"',
            'Content-Transfer-Encoding' => $encoding,
        ];

        $content = ASN1Helper::encode(
            [
                'contentType' => ASN1Helper::OID_COMPRESSED_DATA,
                'content' => [
                    'version' => 0,
                    'compression' => [
                        'algorithm' => ASN1Helper::OID_ALG_ZLIB,
                    ],
                    'payload' => [
                        'contentType' => ASN1Helper::OID_DATA,
                        'content' => base64_encode(gzcompress($content)),
                    ],
                ],
            ],
            ASN1Helper::getContentInfoMap(),
            [
                'content' => ASN1Helper::getCompressedDataMap(),
            ]
        );

        if ($encoding === MimePart::ENCODING_BASE64) {
            $content = Utils::encodeBase64($content);
        }

        return new MimePart($headers, $content);
    }

    /**
     * Decompress data.
     *
     * @param  string|MimePart  $data
     *
     * @return MimePart
     */
    public static function decompress($data)
    {
        if ($data instanceof MimePart) {
            $data = $data->getBody();
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $data = Utils::normalizeBase64($data);

        $payload = ASN1Helper::decode($data, ASN1Helper::getContentInfoMap());

        if ($payload['contentType'] === ASN1Helper::OID_COMPRESSED_DATA) {
            $compressed = ASN1Helper::decode($payload['content'], ASN1Helper::getCompressedDataMap());
            if (empty($compressed['compression']) || empty($compressed['payload'])) {
                throw new \RuntimeException('Invalid compressed data.');
            }
            $algorithm = $compressed['compression']['algorithm'];
            if ($algorithm === ASN1Helper::OID_ALG_ZLIB) {
                $data = (string) Utils::normalizeBase64($compressed['payload']['content']);
                $data = gzuncompress($data);
            }
        }

        return MimePart::fromString($data);
    }

    /**
     * Create a temporary file into temporary directory.
     *
     * @param  string  $content
     *
     * @return string The temporary file generated
     */
    public static function getTempFilename($content = null)
    {
        $dir = sys_get_temp_dir();
        $filename = tempnam($dir, 'phpas2_');
        if ($content) {
            file_put_contents($filename, $content);
        }

        return $filename;
    }

    private static function loadX509($cert)
    {
        $certInfo = (new X509())->loadX509($cert);

        // TODO: phpspeclib bug ?
        if (! empty($certInfo['tbsCertificate']['extensions'])) {
            $certInfo['tbsCertificate']['extensions'][0]['extnValue'] =
                implode(',', $certInfo['tbsCertificate']['extensions'][0]['extnValue']);
        }

        return $certInfo;
    }
}
