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
     * @throws \Exception
     */
    public static function calculateMIC($payload, $algo = null, $includeHeaders = false)
    {
        if (empty($algo)) {
            $algo = 'sha256';
        } elseif (!in_array($algo, hash_algos())) {
            throw new \Exception('Unknown hash algorithm');
        }
        if ($payload instanceof MimePart) {
            $payload = $includeHeaders ? $payload->toString() : $payload->getBody();
        }
        $digest = base64_encode(openssl_digest($payload, $algo, true));
//        $digest = base64_encode(hash($algo, $payload, true));
        return $digest . ', ' . strtoupper($algo);
    }

    /**
     * Sign data which contains mime headers
     *
     * @param string|MimePart $data
     * @param string $cert
     * @param string $key
     * @param array $headers
     * @return MimePart
     * @throws \Exception
     */
    public static function sign($data, $cert, $key = null, $headers = [])
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename($data->toString());
        }
        $temp = self::getTempFilename();
        if (!openssl_pkcs7_sign($data, $temp, $cert, $key, $headers, PKCS7_BINARY | PKCS7_DETACHED)) {
            throw new \Exception(
                sprintf('Failed to sign S/Mime message. Error: "%s".', openssl_error_string())
            );
        }
        return MimePart::fromString(file_get_contents($temp));
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
//        if (is_string($caInfo)) {
//            $caInfo = [self::getTempFilename($caInfo)];
//        }
        // TODO: implement
        return openssl_pkcs7_verify($data, PKCS7_BINARY | PKCS7_NOSIGS | PKCS7_NOVERIFY);
    }

    /**
     * @param string|MimePart $data
     * @param string|array $cert
     * @param int $cipher
     * @return MimePart
     * @throws \Exception
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
            throw new \Exception(
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
     * @throws \Exception
     */
    public static function decrypt($data, $cert, $key = null)
    {
        if ($data instanceof MimePart) {
            $data = self::getTempFilename((string)$data);
        }
        $temp = self::getTempFilename();

        if (!openssl_pkcs7_decrypt($data, $temp, $cert, $key)) {
            throw new \Exception(
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
        if (!($data instanceof MimePart)) {
            $data = MimePart::fromString(is_file($data) ? file_get_contents($data) : $data);
        }
        if ($data->isCompressed()) {
            return gzdecode(base64_decode($data->getBody()));
        }
        return false;
    }

    /**
     * @param string $file
     * @throws \Exception
     */
    public static function checkFileReadable($file)
    {
        if (!is_readable($file)) {
            throw new \Exception('File does not exist or is not readable.');
        }
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
