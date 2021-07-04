<?php

use AS2\PartnerInterface;

$resources = __DIR__.'/../resources';

// local certificates
openssl_pkcs12_read(file_get_contents($resources.'/phpas2.p12'), $local, null);

// mendelson key3
openssl_pkcs12_read(file_get_contents($resources.'/key3.pfx'), $key3, 'test');

return [

    [
        'id'                        => '3770002306000',
        'email'                     => 'support@oscss-shop.fr',
        'target_url'                => 'http://as2.pulpedevie.com/',
        'certificate'               => '-----BEGIN CERTIFICATE-----
MIID0TCCArmgAwIBAgIUbuBEbAxOhiVb7TLCw8gwOfv3Q3AwDQYJKoZIhvcNAQEF
BQAweDELMAkGA1UEBhMCRlIxGTAXBgNVBAgMEEJvdWNoZXMtZHUtUmhvbmUxEjAQ
BgNVBAcMCU1BUlNFSUxMRTEVMBMGA1UECgwMQklPIFBST1ZFTkNFMQswCQYDVQQL
DAJJVDEWMBQGA1UEAwwNcHVscGVkZXZpZS5mcjAeFw0yMDA2MTcxMzM1NDJaFw0y
MzA2MTcxMzM1NDJaMHgxCzAJBgNVBAYTAkZSMRkwFwYDVQQIDBBCb3VjaGVzLWR1
LVJob25lMRIwEAYDVQQHDAlNQVJTRUlMTEUxFTATBgNVBAoMDEJJTyBQUk9WRU5D
RTELMAkGA1UECwwCSVQxFjAUBgNVBAMMDXB1bHBlZGV2aWUuZnIwggEiMA0GCSqG
SIb3DQEBAQUAA4IBDwAwggEKAoIBAQDKFUha+jIa+AaP8h587hg4+CWEDs+SuzCP
UUJ6TWZUs3RWm11VsTuWbptl2wFjaWfboLnm7hCRS40ymfNGxoawVd9QHHsIMe17
9BBr2uZ+OLLzjneqM9sFJdrBPzMLR7k+Nd+HounM5KmnVbSmZKMGwkZYRGVWF35E
zluzbt049ZxXgF+9AfpQCrXRfd+PG+f9lOq/vTWHju6WiZM33k9XeA2t4DcoYX3u
IYumG7l6d0MrP1025JR32gshqpiqBbLSfPzM6IyUN6LYv0HSOKPcyPSWCxZaP5jT
g3pKP4t+eezw68r1nhqx2GNKS79AB0Syj6E/XU05X2xTlU+peqOfAgMBAAGjUzBR
MB0GA1UdDgQWBBS+yt/ro23XracWpRgPve1ufWVibTAfBgNVHSMEGDAWgBS+yt/r
o23XracWpRgPve1ufWVibTAPBgNVHRMBAf8EBTADAQH/MA0GCSqGSIb3DQEBBQUA
A4IBAQDGS6td70R5L/OKhNLJWRbRtO3QMYmBpRCyxQ3YAt5iGmkIuM9DvXGwk11U
AGSckKJRw3IH6t8emWfE09d9COdF3umKVh3+eVUsjdImqx/5/mWZ21L4Doe2eXLB
ORJwa2GBDByw68skQk/OUhGe9DmIPJRO9BmJJL4lcobSunkcB1gURIyoWr9l/onl
0f8gMQmKksaHKpkDXN2XDZtv1rgzO36xNSKeYwMBMcSjEbMPGg8Rvq0q6N2AwBtA
BTANITTXt0gwKWTZrDumf1X/OIZpOPiLPmoDOUJbAioJ+mCQ0GH48Ckm13bGqVpX
Dyq2lN+c7kS0bY1K3XjmlZ7gHix1
-----END CERTIFICATE-----
',
        'private_key'               => '-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDKFUha+jIa+AaP
8h587hg4+CWEDs+SuzCPUUJ6TWZUs3RWm11VsTuWbptl2wFjaWfboLnm7hCRS40y
mfNGxoawVd9QHHsIMe179BBr2uZ+OLLzjneqM9sFJdrBPzMLR7k+Nd+HounM5Kmn
VbSmZKMGwkZYRGVWF35Ezluzbt049ZxXgF+9AfpQCrXRfd+PG+f9lOq/vTWHju6W
iZM33k9XeA2t4DcoYX3uIYumG7l6d0MrP1025JR32gshqpiqBbLSfPzM6IyUN6LY
v0HSOKPcyPSWCxZaP5jTg3pKP4t+eezw68r1nhqx2GNKS79AB0Syj6E/XU05X2xT
lU+peqOfAgMBAAECggEAZghs6hKdreRBW/jB0A5fiJQyTQU1ZT7Ce/ppeFsQKgAZ
44i6jYPZNFFQgRMdFlaoK8pxUtos30+oUT5OCRQ/+VTCVi6rKC4dXJKUoAB8lIqI
QFVUsklQcr70PtJsMWvbaj/FRzTIm71ws56ggcsaTVVWM0cFa3ydMpyGzIhThmgF
8osW/KBADnzwpIvLjjs8SPlpep6YPEWT5rzhWRIGbOzawfl2pBnBNUJ/KRjogCwp
Hfv8ki0h7dr7dmAiTjPAtcbbLRFToRW7OnESeNr3NuV31CliwGALqezlmj7Fj4ke
sjSGEo9DjntJ+Nm0pmqOxZTSkbu+inW5IUWq4seT8QKBgQD6bhrCy12U/cFL6yxV
MnFGLVWOYzkqk1i5PwhFipBio4r0XC/TevQdE/j2Gd0kPxdsjFZDoSkta/+dSX0A
l6zsLNKBj8bwTGLsAPuG1Pty7MomNObknLUVG4Hwp6l3Bz5AidFFEWIhHshYAHPU
nLMRjYJSfDZLCTeboFwviq6xewKBgQDOk+adXSd2Pj3YvIXOrMOOFXXaX3SAVy2e
mhRDzjxV9T453iaZE/ak7OISF7+BtlE8xaVyqVK2MsK6081orOfY6KrMiMZQjYK0
hUKtzovHGoJ9qPEkV7kOjswVDZQto0t4M8U37jBCz1a4lZ3xeeIL72kPpNKtyBUY
nFUdZMMDLQKBgQCr/Qwx9dsKZQ/opNWomWEEEkRs6qYrIFDRwIFcySIKLElVMy7B
bfLTOZFE61Rd/VqH+QWRotAV2tMNYZgQ3RoshUf5JRY6mCtj6/TSj9k0/3yBqtlb
7mfK3D5sWalgDsBpMH1hkuOy3WI4Ve82+HtetbHoFlhvRiBDqGlHWVZKmwKBgFD5
RodekW5W/XUsiKK3s7vJC7Y6fnckNPybVuAxQhNLm0Whn62XVrHVLNR8vJOCvJs+
uhiU6JgEk7IZ/cVPKV4r7W9ZGatPnPFX3wg0EzRLXuUUyNk/DYn4TWTfOrsc7CNE
38SJuB8oGM0n0I5sAUA+awc3y2FVMXfBJ9fqvEpNAoGBAIy5OYvIm4dlIs3gQNha
fTkn26ULoAghamChlSXbqe2ECzmp1yTqg+UaxzWLnq5gsMpZC353Y/KRqo48ymyn
+PqHiywaIhpxPDJwPKn/y7rLUJWsaU9aK39Jd4TzqY3e2Z6desqtVUF+ogr/Zgy8
CZ+8cgwzJdaIiOs2xZ00O7qc
-----END PRIVATE KEY-----
',
        // 'private_key_pass_phrase' => 'password',
        // 'content_type' => 'application/edi-x12',
        'content_type'              => 'Text/Plain',
        'compression'               => false,
        'signature_algorithm'       => 'sha256',
        'signature_algorithm_required' => false,
        'encryption_algorithm'      => '3des',
        'content_transfer_encoding' => 'base64',
        'mdn_mode'                  => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options'               => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    // add your partners here ...

    [
        /** @see http://mendelson-e-c.com/as2/#testserversetup */

        'id' => 'mendelsontestAS2',
        'target_url' => 'http://testas2.mendelson-e-c.com:8080/as2/HttpReceiver',

        // key4
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
        'signature_algorithm' => 'sha256',
        'encryption_algorithm' => '3des',
        // 'content_transfer_encoding' => 'binary',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    [
        /** @see http://mendelson-e-c.com/as2_software */

        'id' => 'mycompanyAS2',
        // 'target_url' => 'http://127.0.0.1:8000',
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
