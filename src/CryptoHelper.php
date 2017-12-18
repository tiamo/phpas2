<?php

namespace AS2;

use Zend\Mime\Mime;

/**
 * TODO: implement pkcs7
 */
class CryptoHelper
{
    /**
     * Extract the message integrity check (MIC) from the digital signature
     *
     * @param MimePart|string $payload
     * @param string $algo
     * @param bool $includeHeaders
     * @return string
     */
    public static function calculateMIC($payload, $algo = null, $includeHeaders = false)
    {
        if (empty($algo) || !in_array($algo, hash_algos())) {
            // TODO: exception ?
            $algo = 'sha256';
        }
        if ($payload instanceof MimePart) {
            $payload = $includeHeaders ? $payload->toString() : $payload->getBody();
        }
        return base64_encode(hash($algo, $payload, true)) . ', ' . strtoupper($algo);
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
//        $mime = new Mime();
//
//        $message = new MimePart();
//        $message->addHeader('MIME-Version', '1.0');
//
//        $contentType = new ContentType();
//        $contentType->setType('multipart/signed');
//        $contentType->addParameter('protocol', 'application/x-pkcs7-signature');
//        $contentType->addParameter('micalg', 'sha-256');
//        $contentType->addParameter('boundary', '--' . $mime->boundary());
//        $message->addHeader($contentType);
//        $message->setBody('This is an S/MIME signed message');
//
//        if ($payload instanceof MimePart) {
//            $message->addPart($data);
//        } else {
//            $message->addPart(file_get_contents($data));
//        }
//
//        $rsa = Rsa::factory([
//            'public_key' => $cert,
//            'private_key' => $key,
//            'binary_output' => false
//        ]);
//
//        $signature = new MimePart();
//        $signature->setHeaders([
//            'content-type' => 'application/x-pkcs7-signature; name="smime.p7s"',
//            'content-disposition' => 'attachment; filename="smime.p7s"',
//            'content-encoding' => Mime::ENCODING_BASE64,
//        ]);
//        $signature->setBody($rsa->sign($message->toString()));
//
//        $message->addPart($signature);
//
//        return $message;

        if ($data instanceof MimePart) {
            $temp = self::getTempFilename();
            file_put_contents($temp, $data->toString());
            $data = $temp;
        }

        $temp = self::getTempFilename();
        if (!openssl_pkcs7_sign($data, $temp, $cert, $key, $headers)) {
            throw new \Exception(openssl_error_string());
        }

        return MimePart::fromString(file_get_contents($temp));
    }

    /**
     * @param string $file
     * @return bool
     */
    public static function verify($file)
    {
        return openssl_pkcs7_verify($file, PKCS7_NOVERIFY);
    }

    /**
     * @param string|MimePart $data
     * @param string|array $cert
     * @param int $cipher
     * @return mixed
     * @throws \Exception
     */
    public static function encrypt($data, $cert, $cipher = OPENSSL_CIPHER_RC2_40)
    {
//        $content = file_get_contents($data);
//        $rsa = Rsa::factory([
//            'public_key' => $cert[0],
//            'private_key' => $cert[1],
//            'binary_output' => false,
//            'pass_phrase' => 'password',
////            'openssl_padding' => OPENSSL_NO_PADDING,
////            'hash_algorithm' => '',
//        ]);
//
//        $part = new MimePart();
//        $part->addHeader('content-type', MimePart::TYPE_X_PKCS7_MIME . '; name="smime.p7m"; smime-type=' . MimePart::SMIME_TYPE_ENCRYPTED);
//        $part->addHeader('content-disposition', 'attachment; filename="smime.p7m"');
//        $part->addHeader('content-description', 'S/MIME Encrypted Message');
//        $part->addHeader('content-transfer-encoding', Mime::ENCODING_BASE64);
//        $part->setBody(Mime::encode($rsa->encrypt($data), Mime::ENCODING_BASE64));
//
//        print_r((string)$rsa->decrypt($rsa->encrypt($data)));
//        exit;
//
//        return $part;

        if ($data instanceof MimePart) {
            $file = self::getTempFilename();
            file_put_contents($file, $data);
            $data = $file;
        }

        $temp = self::getTempFilename();
        if (openssl_pkcs7_encrypt($data, $temp, (array)$cert, [], PKCS7_BINARY, $cipher)) {
            return MimePart::fromString(file_get_contents($temp));
        }

        return false;
    }

    /**
     * @param string|MimePart $data
     * @param mixed $cert
     * @param mixed $key
     * @return MimePart|false
     * @throws \Exception
     */
    public static function decrypt($data, $cert, $key = null)
    {
        if ($data instanceof MimePart) {
            $temp = self::getTempFilename();
            file_put_contents($temp, $data->toString());
            $data = $temp;
        }
        $temp = self::getTempFilename();
        if (openssl_pkcs7_decrypt($data, $temp, $cert, $key)) {
            return MimePart::fromString(file_get_contents($temp));
        }
        return false;
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
        $payload = new MimePart();
        $payload->setHeaders([
            'content-type' => MimePart::TYPE_X_PKCS7_MIME . '; name="smime.p7z"; smime-type=' . MimePart::SMIME_TYPE_COMPRESSED,
            'content-description' => 'S/MIME Compressed Message',
            'content-disposition' => 'attachment; filename="smime.p7z"',
            'content-encoding' => $encoding,
        ]);
        $payload->setBody(Mime::encode(gzencode($content), $encoding));
        return $payload;
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
     * @return string The temporary file generated
     */
    public static function getTempFilename()
    {
        $dir = sys_get_temp_dir();
        $filename = tempnam($dir, 'as2file_');
        return $filename;
    }

}