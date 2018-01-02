<?php

namespace AS2;

use Zend\Mime\Mime;

/**
 * TODO: Implement pure methods without "openssl_pkcs7"
 * openssl_pkcs7 doesn't work with binary data
 */
class CryptoHelper
{
    /**
     * Extract the message integrity check (MIC) from the digital signature
     *
     * @param MimePart|string $payload
     * @param string $algo Default is SHA256
     * @param bool $includeHeaders
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function calculateMIC($payload, $algo = 'sha256', $includeHeaders = true)
    {
        $digestAlgorithm = str_replace('-', '', strtolower($algo));
        if (!in_array($digestAlgorithm, hash_algos())) {
            throw new \InvalidArgumentException('Unknown hash algorithm');
        }
        if (!($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }
//        $digest = base64_encode(openssl_digest($payload, $digestAlgorithm, true));
        $digest = base64_encode(hash(
            $digestAlgorithm,
            $includeHeaders ? $payload : $payload->getBody(),
            true
        ));

        return $digest . ', ' . $algo;
    }

    /**
     * Sign data which contains mime headers
     *
     * @param string|MimePart $data
     * @param string $cert
     * @param string $key
     * @param array $headers
     * @param array $micAlgo
     * @return MimePart
     * @throws \RuntimeException
     */
    public static function sign($data, $cert, $key = null, $headers = [], $micAlgo = null)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename($data->toString());
        }
        $temp = self::getTempFilename();
        if (!openssl_pkcs7_sign($data, $temp, $cert, $key, $headers, PKCS7_DETACHED)) {
            throw new \RuntimeException(
                sprintf('Failed to sign S/Mime message. Error: "%s".', openssl_error_string())
            );
        }
        $payload = MimePart::fromString(file_get_contents($temp));

        // TODO: refactory
        // Some servers doesn't support "x-pkcs7"
        $contentType = $payload->getHeaderLine('content-type');
        $contentType = str_replace('x-pkcs7', 'pkcs7', $contentType);
        if ($micAlgo) {
            $contentType = preg_replace('/micalg=(.+);/i', 'micalg="'. $micAlgo .'";', $contentType);
        }
        $payload = $payload->withHeader('Content-Type', $contentType);
        foreach ($payload->getParts() as $key => $part) {
            if ($part->isPkc7Signature()) {
                $payload->removePart($key);
                $payload->addPart(
                    $part->withHeader('Content-Type', 'application/pkcs7-signature; name=smime.p7s; smime-type=signed-data')
                );
            }
        }

        return $payload;
    }

    /**
     * @param string|MimePart $data
     * @param array $caInfo Information about the trusted CA certificates to use in the verification process
     * @return bool
     */
    public static function verify($data, $caInfo = [])
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string)$data);
        }
        return openssl_pkcs7_verify($data, PKCS7_BINARY | PKCS7_NOSIGS | PKCS7_NOVERIFY);
    }

    /**
     * @param string|MimePart $data
     * @param string|array $cert
     * @param int $cipher
     * @return MimePart
     * @throws \RuntimeException
     */
    public static function encrypt($data, $cert, $cipher = OPENSSL_CIPHER_3DES)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string)$data);
        }
        // Get cipher by name
        if (is_string($cipher) && defined('OPENSSL_CIPHER_' . strtoupper($cipher))) {
            $cipher = constant('OPENSSL_CIPHER_' . strtoupper($cipher));
        }
        $temp = self::getTempFilename();
        if (!openssl_pkcs7_encrypt($data, $temp, (array)$cert, [], PKCS7_BINARY, $cipher)) {
            throw new \RuntimeException(
                sprintf('Failed to encrypt S/Mime message. Error: "%s".', openssl_error_string())
            );
        }
        return MimePart::fromString(file_get_contents($temp));
    }

    /**
     * @param string|MimePart $data
     * @param mixed $cert
     * @param mixed $key
     * @return MimePart
     * @throws \RuntimeException
     */
    public static function decrypt($data, $cert, $key = null)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string)$data);
        }
        $temp = self::getTempFilename();
        if (!openssl_pkcs7_decrypt($data, $temp, $cert, $key)) {
            throw new \RuntimeException(
                sprintf('Failed to decrypt S/Mime message. Error: "%s".', openssl_error_string())
            );
        }
        return MimePart::fromString(file_get_contents($temp));
    }

    /**
     * Compress data
     *
     * @param string|MimePart $data
     * @param string $encoding
     * @return MimePart
     */
    public static function compress($data, $encoding = Mime::ENCODING_BASE64)
    {
        if ($data instanceof MimePart) {
            $content = $data->toString();
        } else {
            $content = is_file($data) ? file_get_contents($data) : $data;
        }
        return new MimePart([
            'Content-Type' => MimePart::TYPE_X_PKCS7_MIME . '; name="smime.p7z"; smime-type=' . MimePart::SMIME_TYPE_COMPRESSED,
            'Content-Description' => 'S/MIME Compressed Message',
            'Content-Disposition' => 'attachment; filename="smime.p7z"',
            'Content-Encoding' => $encoding,
        ], Mime::encode(gzencode($content), $encoding));
    }

    /**
     * @param string|MimePart $data
     * @return string
     * @throws \Exception
     */
    public static function decompress($data)
    {
        if ($data instanceof MimePart) {
            $data = $data->getBody();
        }
//        if ($data->isCompressed()) {
        return gzdecode(base64_decode($data));
//        }
//        return false;
    }

    /**
     * Create a temporary file into temporary directory
     *
     * @param string $content
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
