<?php

namespace AS2;

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
     * @param  string|resource  $cert
     * @param  string|resource  $privateKey
     * @param  array  $headers
     * @param  array  $micAlgo
     *
     * @return MimePart
     */
    public static function sign($data, $cert, $privateKey = null, $headers = [], $micAlgo = null)
    {
        $data = self::getTempFilename((string) $data);
        $temp = self::getTempFilename();

        if (! openssl_pkcs7_sign($data, $temp, $cert, $privateKey, $headers)) {
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
    public static function verify($data, $caInfo = null, $rootCerts = null)
    {
        if ($data instanceof MimePart) {
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

        $outFile = stripos(PHP_OS, 'WIN') === 0 ?
            self::getTempFilename() :
            '/dev/null';

        return openssl_pkcs7_verify($data, $flags, $outFile, $rootCerts) === true;
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
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string) $data);
        }

        if (is_string($cipher)) {
            $cipher = strtoupper($cipher);
            $cipher = \str_replace('-', '_', $cipher);
            if (defined('OPENSSL_CIPHER_'.$cipher)) {
                $cipher = constant('OPENSSL_CIPHER_'.$cipher);
            }
        }

        $temp = self::getTempFilename();
        if (! openssl_pkcs7_encrypt($data, $temp, (array) $cert, [], PKCS7_BINARY, $cipher)) {
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

        if ($payload['contentType'] === ASN1Helper::COMPRESSED_DATA_OID) {
            $compressed = ASN1Helper::decode($payload['content'], ASN1Helper::getCompressedDataMap());
            if (empty($compressed['compression']) || empty($compressed['payload'])) {
                throw new \RuntimeException('Invalid compressed data.');
            }
            $algorithm = $compressed['compression']['algorithm'];
            if ($algorithm === ASN1Helper::ALG_ZLIB_OID) {
                $data = gzuncompress(base64_decode($compressed['payload']['content']));
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
}
