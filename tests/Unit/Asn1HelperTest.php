<?php

namespace AS2\Tests\Unit;

use AS2\ASN1Helper;
use AS2\Tests\TestCase;
use AS2\Utils;
use phpseclib3\File\ASN1;
use phpseclib3\Math\BigInteger;

/**
 * @see ASN1Helper
 *
 * @internal
 *
 * @coversNothing
 */
final class Asn1HelperTest extends TestCase
{
    public function testSignerInfoMap(): void
    {
        $data = 'MYICsjCCAq4CAQEwgZ8wgZYxITAfBgkqhkiG9w0BCQEWEnZrLnRpYW1vQGdtYWlsLmNvbTELMAkGA1UEBhMCdWsxEzARBgNVBAgMClN0YXRlIG5hbWUxEDAOBgNVBAcMB1VrcmFpbmUxDzANBgNVBAoMBnBocGFzMjEPMA0GA1UECwwGcGhwYXMyMRswGQYDVQQDDBJwaHBhczIuZXhhbXBsZS5jb20CBF15iogwDQYJYIZIAWUDBAIBBQCggeQwGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkqhkiG9w0BCQUxDxcNMjAwNjI2MTQ0NzI2WjAvBgkqhkiG9w0BCQQxIgQgErAZ6kGin4i4PUDRmkdkKtzTd7tA9j77MxJe/yOogxAweQYJKoZIhvcNAQkPMWwwajALBglghkgBZQMEASowCwYJYIZIAWUDBAEWMAsGCWCGSAFlAwQBAjAKBggqhkiG9w0DBzAOBggqhkiG9w0DAgICAIAwDQYIKoZIhvcNAwICAUAwBwYFKw4DAgcwDQYIKoZIhvcNAwICASgwDQYJKoZIhvcNAQEBBQAEggEAKQmPfNi4f1mPAjz/4+4YcD+Apeeq0YaXL+SnjmAu1O/iHG4rTyq25NBigA49c8Oj0/sjqZo149y0bfJwnWnh6+Fd7jQNih7LzvEAq0W5TDB8+4xCp41zQK0ZB44rdHLogm8o73QgY4tC2haxflpWlLckMqc1F332+bUi0ImRdUw64Z/jAfQwSEb6LwMHMowyYCsJd4Qiu/fQfJXbdAqd/LXXBqXUHkEaeOnjMKehnc/UyJUVJYqKyuC46Hb6YA5t92LquGqiGomGWcGGilVQwWK3/mVC6Yu8gpTNbAVZmueOrxXq+IqaLb5PAC07D6qAB/5YhN28woc7NDD9R4zHUA==';

        $data = Utils::normalizeBase64($data);

        $payload = ASN1Helper::decode($data, [
            'type' => ASN1::TYPE_SET,
            'min' => 1,
            'max' => -1,
            'children' => ASN1Helper::getSignerInfoMap(),
        ]);

        self::assertSame($payload[0]['version'], '1');
        self::assertSame($payload[0]['signatureAlgorithm']['algorithm'], '1.2.840.113549.1.1.1');
    }

    public function testSignedData(): void
    {
        $data = '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';

        $data = Utils::normalizeBase64($data);

        $payload = ASN1Helper::decode($data, ASN1Helper::getSignedDataMap());

        self::assertSame(ASN1Helper::OID_SIGNED_DATA, $payload['contentType']);
        self::assertSame('1', $payload['content']['version']);
        self::assertSame(ASN1Helper::OID_SHA256, $payload['content']['digestAlgorithms'][0]['algorithm']);
        self::assertSame(ASN1Helper::OID_DATA, $payload['content']['contentInfo']['contentType']);

        self::assertSame(
            (string) new BigInteger(1568246408),
            (string) $payload['content']['certificates'][0]['tbsCertificate']['serialNumber']
        );
        self::assertSame(ASN1Helper::OID_SHA256, $payload['content']['signers'][0]['digestAlgorithm']['algorithm']);
        self::assertSame(
            ASN1Helper::OID_RSA_ENCRYPTION,
            $payload['content']['signers'][0]['signatureAlgorithm']['algorithm']
        );
    }
}
