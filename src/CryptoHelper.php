<?php

namespace AS2;

use InvalidArgumentException;
use RuntimeException;

/**
 * TODO: Implement pure methods without "openssl_pkcs7"
 * check openssl_pkcs7 doesn't work with binary data
 */
class CryptoHelper
{
    /**
     * Extract the message integrity check (MIC) from the digital signature
     *
     * @param  MimePart|string  $payload
     * @param  string  $algo  Default is SHA256
     * @param  bool  $includeHeaders
     * @return string
     * @throws InvalidArgumentException
     */
    public static function calculateMIC($payload, $algo = 'sha256', $includeHeaders = true)
    {
        $digestAlgorithm = str_replace('-', '', strtolower($algo));

        if (! in_array($digestAlgorithm, hash_algos(), true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid hash algorithm `%s`.', $digestAlgorithm)
            );
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
     * Sign data which contains mime headers
     *
     * @param  string|MimePart  $data
     * @param  string|resource  $cert
     * @param  string|resource  $privateKey
     * @param  array  $headers
     * @param  array  $micAlgo
     * @return MimePart
     * @throws RuntimeException
     */
    public static function sign($data, $cert, $privateKey = null, $headers = [], $micAlgo = null)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename($data->toString());
        }
        $temp = self::getTempFilename();

        if (! openssl_pkcs7_sign($data, $temp, $cert, $privateKey, $headers, PKCS7_DETACHED)) {
            throw new RuntimeException(
                sprintf('Failed to sign S/Mime message. Error: "%s".', openssl_error_string())
            );
        }
        $payload = MimePart::fromString(file_get_contents($temp), false);

        $contentType = $payload->getHeaderLine('content-type');
        if ($micAlgo) {
            $contentType = preg_replace('/micalg=(.+);/i', 'micalg="'.$micAlgo.'";', $contentType);
        }

        /** @var MimePart $payload */
        $payload = $payload->withHeader('Content-Type', $contentType);

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
     * Create a temporary file into temporary directory
     *
     * @param  string  $content
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

    /**
     * @param  string|MimePart  $data
     * @param  array|null  $caInfo  Information about the trusted CA certificates to use in the verification process
     * @param  array  $rootCerts
     * @return bool
     */
    public static function verify($data, $caInfo = null, $rootCerts = [])
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string) $data);
        }

        if (! empty($caInfo)) {
            if (! is_array($caInfo)) {
                $caInfo = [$caInfo];
            }
            foreach ($caInfo as $cert) {
                $rootCerts[] = self::getTempFilename($cert);
            }
        }

        if (! empty($rootCerts) && openssl_pkcs7_verify($data, 0, null, $rootCerts) === true) {
            return true;
        }

        // return openssl_pkcs7_verify($data, PKCS7_BINARY | PKCS7_NOSIGS | PKCS7_NOVERIFY, null, $caInfo);
        // Message verified successfully but the signer's certificate could not be verified.

        return openssl_pkcs7_verify($data, PKCS7_BINARY | PKCS7_NOSIGS | PKCS7_NOVERIFY) === true;
    }

    /**
     * @param  string|MimePart  $data
     * @param  string|array  $cert
     * @param  int|string  $cipher
     * @return MimePart
     * @throws RuntimeException
     */
    public static function encrypt($data, $cert, $cipher = OPENSSL_CIPHER_AES_128_CBC)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string) $data);
        }

        if (is_string($cipher) && defined('OPENSSL_CIPHER_'.strtoupper($cipher))) {
            $cipher = constant('OPENSSL_CIPHER_'.strtoupper($cipher));
        }

        $temp = self::getTempFilename();
        if (! openssl_pkcs7_encrypt($data, $temp, (array) $cert, [], PKCS7_BINARY, $cipher)) {
            throw new RuntimeException(
                sprintf('Failed to encrypt S/Mime message. Error: "%s".', openssl_error_string())
            );
        }

        return MimePart::fromString(file_get_contents($temp), false);
    }

    /**
     * @param  string|MimePart  $data
     * @param  mixed  $cert
     * @param  mixed  $key
     * @return MimePart
     * @throws RuntimeException
     */
    public static function decrypt($data, $cert, $key = null)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string) $data);
        }

        $temp = self::getTempFilename();
        if (! openssl_pkcs7_decrypt($data, $temp, $cert, $key)) {
            throw new RuntimeException(
                sprintf('Failed to decrypt S/Mime message. Error: "%s".', openssl_error_string())
            );
        }

        return MimePart::fromString(file_get_contents($temp));
    }

    /**
     * Compress data
     *
     * @param  string|MimePart  $data
     * @param  string  $encoding
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
                'contentType' => ASN1Helper::COMPRESSED_DATA_OID,
                'content' => [
                    'version' => 0,
                    'compression' => [
                        'algorithm' => ASN1Helper::ALG_ZLIB_OID,
                    ],
                    'payload' => [
                        'contentType' => ASN1Helper::DATA_OID,
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
     * Decompress data
     *
     * @param  string|MimePart  $data
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

        if ($payload['contentType'] === ASN1Helper::COMPRESSED_DATA_OID) {
            $compressed = ASN1Helper::decode($payload['content'], ASN1Helper::getCompressedDataMap());
            if (empty($compressed['compression']) || empty($compressed['payload'])) {
                throw new RuntimeException('Invalid compressed data.');
            }
            $algorithm = $compressed['compression']['algorithm'];
            if ($algorithm === ASN1Helper::ALG_ZLIB_OID) {
                $data = gzuncompress(base64_decode($compressed['payload']['content']));
            }
        }

        return MimePart::fromString($data);
    }
}
