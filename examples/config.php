<?php

use AS2\PartnerInterface;

// load certificates
openssl_pkcs12_read(file_get_contents(__DIR__.'/data/phpas2.12'), $certs, null);

return [
    'storage_path' => __DIR__.'/tmp/storage',
    'log_path' => 'php://stdout',
    // 'log_path' => __DIR__.'/tmp/logs.txt',
    'partners' => [

        // add your partners here ...

        [
            /** @see http://mendelson-e-c.com/as2_software */

            'id' => 'mycompanyAS2',
            'target_url' => 'http://127.0.0.1:8080/as2/HttpReceiver',
            'certificate' => '-----BEGIN CERTIFICATE-----
MIIC0DCCAjkCBEOO/bswDQYJKoZIhvcNAQEFBQAwga4xJjAkBgkqhkiG9w0BCQEWF3Jvc2V0dGFu
ZXRAbWVuZGVsc29uLmRlMQswCQYDVQQGEwJERTEPMA0GA1UECBMGQmVybGluMQ8wDQYDVQQHEwZC
ZXJsaW4xIjAgBgNVBAoTGW1lbmRlbHNvbi1lLWNvbW1lcmNlIEdtYkgxIjAgBgNVBAsTGW1lbmRl
bHNvbi1lLWNvbW1lcmNlIEdtYkgxDTALBgNVBAMTBG1lbmQwHhcNMDUxMjAxMTM0MjE5WhcNMTkw
ODEwMTM0MjE5WjCBrjEmMCQGCSqGSIb3DQEJARYXcm9zZXR0YW5ldEBtZW5kZWxzb24uZGUxCzAJ
BgNVBAYTAkRFMQ8wDQYDVQQIEwZCZXJsaW4xDzANBgNVBAcTBkJlcmxpbjEiMCAGA1UEChMZbWVu
ZGVsc29uLWUtY29tbWVyY2UgR21iSDEiMCAGA1UECxMZbWVuZGVsc29uLWUtY29tbWVyY2UgR21i
SDENMAsGA1UEAxMEbWVuZDCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAvl9YOib23cCSOpkD
DU+NRnMnB1G8AhViieKhw2h33895+IfrkCSaEL3PMi0wn55ddPRgdMi9mOWELU6ITkvSMMsjFgYY
e+1ibQjfK3Tnw9g1te/O+7XvjZaboEb4Onjh+p6fVZ90WTg1ccU8sifKSPFTJ59d2HsjDMO1VWhD
uYUCAwEAATANBgkqhkiG9w0BAQUFAAOBgQC8DiHP61jAADXRIfxoDvw0pFTMMTOVAa905GGy1P+Y
4NC8I92PviobpmEq8Z2HsEi6iviVwODrPTSfm93mUWZ52EPXinlGYHRP0D/VxNOMvFi+mRyweLA5
5rIFWk1PqdJRch9E3vTcjwRtCfPNdPQlynVwk0jeYKtEtQn2J9LLWg==
-----END CERTIFICATE-----
',
            'content_type' => 'application/EDI-Consent',
            'compression' => true,
            'signature_algorithm' => 'sha256',
            'encryption_algorithm' => '3des',
            'content_transfer_encoding' => 'binary',
            'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
            'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
        ],

        // local station

        [
            'id' => 'phpas2',
            'email' => 'phpas2@example.com',
            'target_url' => 'http://127.0.0.1:8000',
            'certificate' => $certs['cert'],
            'private_key' => $certs['pkey'],
            // 'private_key_pass_phrase' => 'password',
            // 'content_type' => 'application/edi-x12',
            'content_type' => 'application/EDI-Consent',
            'compression' => false,
            'signature_algorithm' => 'sha256',
            'encryption_algorithm' => '3des',
            'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
            'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
        ],
    ],
];
