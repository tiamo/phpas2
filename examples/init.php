<?php

use AS2\PartnerInterface;

require_once "bootstrap.php";

$storage = new \models\FileStorage();

$storage->savePartner($storage->initPartner([
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
    'compression' => false,
    'signature_algorithm' => 'sha1',
    'encryption_algorithm' => '3des',
    'content_transfer_encoding' => 'binary',
    'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
    'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha1'
]));

$storage->savePartner($storage->initPartner([
    'id' => 'phpas2',
    'target_url' => 'http://127.0.0.1/as2/examples/inbound.php',
    'certificate' => '-----BEGIN CERTIFICATE-----
MIIECTCCAvGgAwIBAgIBADANBgkqhkiG9w0BAQsFADCBnjELMAkGA1UEBhMCRUUx
ETAPBgNVBAgMCEhhcmp1bWFhMRAwDgYDVQQHDAdUYWxsaW5uMRUwEwYDVQQKDAxU
ZXNsYWFtYXppbmcxFTATBgNVBAsMDFRlc2xhYW1hemluZzEVMBMGA1UEAwwMVEVT
TEFBTUFaSU5HMSUwIwYJKoZIhvcNAQkBFhZhZG1pbkB0ZXNsYWFtYXppbmcuY29t
MB4XDTE3MTIyODAwMTEzMFoXDTE4MTIyODAwMTEzMFowgZ4xCzAJBgNVBAYTAkVF
MREwDwYDVQQIDAhIYXJqdW1hYTEQMA4GA1UEBwwHVGFsbGlubjEVMBMGA1UECgwM
VGVzbGFhbWF6aW5nMRUwEwYDVQQLDAxUZXNsYWFtYXppbmcxFTATBgNVBAMMDFRF
U0xBQU1BWklORzElMCMGCSqGSIb3DQEJARYWYWRtaW5AdGVzbGFhbWF6aW5nLmNv
bTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMuVZ8Ewipq7PhCACIlN
tonQgI3FYSQm6OJjvrflT58NsXnfuFeQ1ur2nyZ1mojI3NelbfyxNJcqRD8KmuGv
NC51LFeSLAmdkR27N998STDp9op06MFVKbNU97CV+uDAs801QTJP7fp6EWHKuBNi
hDRedcfIzbQt6UzuOLKAA2P6bHvwPddqDN9THPsn016rYzehzFOT3vN5rODZy0gh
yBuzJF/QThf/qw323DRfOJpTspznS4OMayYVp+pWIvDXzK5bgRJGKd4Osn4ToDuj
PY9VwSgqLGsg3cz941hZnWy1rZCXsRNSXCUTBjKSvIVlQ0UKVdchqYgRPaoa7QXt
pgMCAwEAAaNQME4wHQYDVR0OBBYEFJ/ryNiIkhbrMglVoxZ7CtXCKkVcMB8GA1Ud
IwQYMBaAFJ/ryNiIkhbrMglVoxZ7CtXCKkVcMAwGA1UdEwQFMAMBAf8wDQYJKoZI
hvcNAQELBQADggEBAHQEnhwTnU79UQNzfQTgW0NtIbCSJTXFv3yx7+Jfo5Md9Kux
wIClMb20xYpmcc35DkuEKZJBZjAKlqAc+ZQXMHRoaCa7QwHSIcMNyNl2lLeP4dW5
td9PXL/RDyg6dZaGI1ytdT+f5zVjRL8k6R3J99Z3m3oQrnPIrHV4pA68YzK3UZuS
6BUtzCdBr1jjyk4nbzQ3MEZtas/gWd7cYVw/Rkx4cCtTzc0woMkDzBpaP5teCXtU
/YNAwfSvpYSxYvCT9ccQ+8D+sAJKa3BUgsuUPVTOTNnqsIScutac1qAf9SC1O4VH
OLDK3U7rc1ukEwOBeeT9uabADiea/wU4ecGRAhI=
-----END CERTIFICATE-----
',
            'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDLlWfBMIqauz4Q
gAiJTbaJ0ICNxWEkJujiY7635U+fDbF537hXkNbq9p8mdZqIyNzXpW38sTSXKkQ/
CprhrzQudSxXkiwJnZEduzfffEkw6faKdOjBVSmzVPewlfrgwLPNNUEyT+36ehFh
yrgTYoQ0XnXHyM20LelM7jiygANj+mx78D3XagzfUxz7J9Neq2M3ocxTk97zeazg
2ctIIcgbsyRf0E4X/6sN9tw0XziaU7Kc50uDjGsmFafqViLw18yuW4ESRineDrJ+
E6A7oz2PVcEoKixrIN3M/eNYWZ1sta2Ql7ETUlwlEwYykryFZUNFClXXIamIET2q
Gu0F7aYDAgMBAAECggEBAJsbve1HGpNBTcwsgFR8TTM7FHbvh0+QBadW75wUrlE0
kZ+VgFHXHKfwNtmKiK7murviYqZALR1vKogNgGuqnUs4IwylZb/9uO66EZvIicsm
tpxO7nc+d4MWnZCA9KAAsf0LMh0vINXR4yRq20yJpshvn7UTnQZGZJYkejYlqm+k
pAZJutax1gZILXaxgwvAFj3VQgyaXcZQB24KG/9JNlijM7CcD6xH41gIggWkTgMk
F8NlnM4b9HZuV1yk0RHYut0EbPevNB4eu/3aa/8loZ1BulnV7TYW6CBK8S8lsniZ
t27AU1VX501u/Y7hcwUm2P089pXVmcvhkPA1aQii0MECgYEA9KSGD7yjwNi7e7TY
9Rt67kQZRopcBO8DAF1HSVgUNB+1sTkDI6CAYTUZ1Ik8pP8+LcSYu2iEnokwl1VE
G9UX7ie/mcT/c6nmJH9+MDr4ZEyDgItWq0NUKEAYJ5PzBR+xCfvTkTTl6Xo45lUu
LZ7gRZoDoPf6NXPgG5CcWnxU/GkCgYEA1QjpbQAHYqU1pQNLJbnaNgCzsTuRavv2
zFojcmf025Fh0FHjyN4z9wQixEn/bYaGPGSn5O16gdlwsZ2PTTtSiby7o7pQ1/k0
QPv8xGzw0VSF4g2OgBbHCaMHulyoNj2oILKGOJKB4Bc0OIa2Brw4/4iqXENlTp+1
2ydYgN6ksYsCgYEA3DnOxRPXhZ6VB4OBWwRl4V9EMZATzg0q8oUFyyyS42k4MlVU
UhoF93vJyN3RzeZHnwO/SdWIrP5q05BaQ4PMiwMVI+OG4iQrnOd4PQqY2BFYv7qv
RnTRqXopRFeXCSHCSW58wdaQsWDpH8/GRiMjWQSQB7OzdbLeJ3JCjeImzyECgYA5
rBX1akKVlAA30fJwHiZS5FHBM00k111y6RwbhsUlA/ClAuZVpMIQp0/6L8Y0kmyY
wO8q7Jdzu7fkfbEjyWGI5E0v/+qO7WoWBaHiU1PGd8le7yiayI/NrhMTgq4PMRXo
9Cud+Rur3NxIST/SBvavRwJHw/8TD+2djMNK7/tKDQKBgQDKhTk0X+gqTnPd7Z2u
/d4nltNNKU+Rj+v/Vk+PGzYHFRCJKp/HUphQRpWpssK6UeWWvfm9y7UVDuRlLXz7
0Cg7MUp7pnUCywQdKyI9Q0Jl8K2t3x2ZD9QiVCtvy5VYhbxrSki/e9uubny+mkFw
SrE04Usb4uTQob8VH33EttN4JQ==
-----END PRIVATE KEY-----
',
//            'private_key_pass_phrase' => 'password',
    'content_type' => 'application/edi-x12',
    'compression' => false,
    'signature_algorithm' => 'sha256',
    'encryption_algorithm' => '3des',
    'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
    'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256'
]));
