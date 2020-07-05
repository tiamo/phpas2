<?php

use AS2\PartnerInterface;

$resources = __DIR__.'/../resources';

// local certificates
openssl_pkcs12_read(file_get_contents($resources.'/phpas2.p12'), $local, null);

// mendelson key3
openssl_pkcs12_read(file_get_contents($resources.'/key3.pfx'), $key3, 'test');

return [

    // add your partners here ...

    [
        /** @see http://mendelson-e-c.com/as2/#testserversetup */

        'id' => 'mendelsontestAS2',
        'target_url' => 'http://testas2.mendelson-e-c.com:8080/as2/HttpReceiver',
        'certificate' => '-----BEGIN CERTIFICATE-----
MIIEJTCCAw2gAwIBAgIEWipbyDANBgkqhkiG9w0BAQsFADCBujEjMCEGCSqGSIb3DQEJARYUc2Vy
dmljZUBtZW5kZWxzb24uZGUxCzAJBgNVBAYTAkRFMQ8wDQYDVQQIDAZCZXJsaW4xDzANBgNVBAcM
BkJlcmxpbjEiMCAGA1UECgwZbWVuZGVsc29uLWUtY29tbWVyY2UgR21iSDEhMB8GA1UECwwYRG8g
bm90IHVzZSBpbiBwcm9kdWN0aW9uMR0wGwYDVQQDDBRtZW5kZWxzb24gdGVzdCBrZXkgNDAeFw0x
NzEyMDgwOTMwNDhaFw0yNzEyMDYwOTMwNDhaMIG6MSMwIQYJKoZIhvcNAQkBFhRzZXJ2aWNlQG1l
bmRlbHNvbi5kZTELMAkGA1UEBhMCREUxDzANBgNVBAgMBkJlcmxpbjEPMA0GA1UEBwwGQmVybGlu
MSIwIAYDVQQKDBltZW5kZWxzb24tZS1jb21tZXJjZSBHbWJIMSEwHwYDVQQLDBhEbyBub3QgdXNl
IGluIHByb2R1Y3Rpb24xHTAbBgNVBAMMFG1lbmRlbHNvbiB0ZXN0IGtleSA0MIIBIjANBgkqhkiG
9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyeDD3FzJD3GdWoMj4pcpX7XLc5ZWJyVmt7ci+hCIyVmc4Kz5
JIhAqQmes/EYNBf1CHBQL6yLbVPzfmDhadoXcRtVtosyG6+XvTzP8zaUQ5NcEZPkOA8S14VcvPkI
X4I7NuU5TKkgRQ6G91tnFg3F5Ywm79qBuggxa3VPSofQpq3bJXYkaNI8vMARFyX/bDjNYFzOYCyD
jG6Jwbwg1M69DLK6IGntku6PXGOf3X2BPMNgiZfV29sGIBKoWyx4q3p0qLXKYTPAtYP9+Uzkz+mq
2dcH56L6rFuAMbXYGEwarbby0JsVULc3q8+anlfxrfzDJH1KYzrdYmW6bRi/dh8AWQIDAQABozEw
LzAOBgNVHQ8BAf8EBAMCBaAwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMA0GCSqGSIb3
DQEBCwUAA4IBAQCh7+6IQjfGwsisA7xMNcPsRQC1av9T1eF2WjgmNjY0htKpK+Q2VgsAm3EgraoK
EaUL5LaAJpQvH8iLVLdct3Qn483HVHeCiB/DE/eBrbxLVrUZqysZerWONX97BPbIBCKJAEm3Pqyi
ej7IBY7WKy9OvCErUoH0zXsdfkuJlJXf1jS+qtEbWRGnbxwfXgH0S1uw7QU0q8EECvEb+MNrCEtD
4Wdjq35OFKLLPcChlEgoXabGefFSAeALnIZ2CJDn8Yz+7ZvdXkBjl17z9GYnR54bBz8CUxYqJBgu
0iE784sGpulvrJeeyrNS7EgP3odta2vn5ySjQQI8M8ubL+/cs1T7
-----END CERTIFICATE-----
',
        'content_type' => 'application/EDI-Consent',
        'compression' => true,
        'signature_algorithm' => 'sha1',
        'encryption_algorithm' => '3des',
        // 'content_transfer_encoding' => 'binary',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    [
        /** @see http://mendelson-e-c.com/as2_software */

        'id' => 'mycompanyAS2',
        //     'target_url' => 'http://127.0.0.1:8000',
        'target_url' => 'http://127.0.0.1:8080/as2/HttpReceiver',
        'private_key' => isset($key3['pkey']) ? $key3['pkey'] : null,
        'certificate' => isset($key3['cert']) ? $key3['cert'] : null,
        'content_type' => 'application/EDI-Consent',
        'compression' => true,
        'signature_algorithm' => 'sha256',
        'encryption_algorithm' => '3des',
        // 'content_transfer_encoding' => 'binary',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    // local station

    [
        'id' => 'phpas2',
        'email' => 'phpas2@example.com',
        'target_url' => 'http://127.0.0.1:8000',
        'certificate' => isset($local['cert']) ? $local['cert'] : null,
        'private_key' => isset($local['pkey']) ? $local['pkey'] : null,
        // 'private_key_pass_phrase' => 'password',
        // 'content_type' => 'application/edi-x12',
        'content_type' => 'application/EDI-Consent',
        'compression' => true,
        'signature_algorithm' => 'sha256',
        'encryption_algorithm' => '3des',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],
];
