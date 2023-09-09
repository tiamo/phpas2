<?php

use AS2\PartnerInterface;

$resources = __DIR__.'/../resources';

// local certificates
openssl_pkcs12_read(file_get_contents($resources.'/phpas2.p12'), $local, null);

// mendelson key3
openssl_pkcs12_read(file_get_contents($resources.'/key3.pfx'), $key3, 'test');

return [
    [
        'id' => '3770002306000',
        'email' => 'support@oscss-shop.fr',
        'target_url' => 'http://as2.pulpedevie.com/',
        'certificate' => '-----BEGIN CERTIFICATE-----
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
        'private_key' => '-----BEGIN PRIVATE KEY-----
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
        'content_type' => 'Text/Plain',
        'compression' => false,
        'signature_algorithm' => 'sha256',
        'signature_algorithm_required' => false,
        'encryption_algorithm' => '3des',
        'content_transfer_encoding' => 'base64',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    // add your partners here ...

    [
        // @see http://mendelson-e-c.com/as2/#testserversetup

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
        // @see http://mendelson-e-c.com/as2_software

        'id' => 'mycompanyAS2',
        // 'target_url' => 'http://127.0.0.1:8000',
        'target_url' => 'http://127.0.0.1:8080/as2/HttpReceiver',
        'private_key' => $key3['pkey'] ?? null,
        'certificate' => $key3['cert'] ?? null,
        'content_type' => 'application/EDI-Consent',
        'compression' => true,
        'signature_algorithm' => 'sha256',
        'encryption_algorithm' => '3des',
        // 'content_transfer_encoding' => 'binary',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    [
        'id' => 'webedi',
        'target_url' => 'http://as2.webedi.co.uk:8181/as2connector/pub/ReceiveFile.rsb',
        'certificate' => '-----BEGIN CERTIFICATE-----
MIICwTCCAamgAwIBAgIBAjANBgkqhkiG9w0BAQUFADAkMRAwDgYDVQQDEwdXZWIgRURJMRAw
DgYDVQQKEwdXZWIgRURJMB4XDTEzMTAxMDExMzkxN1oXDTIzMTAwODExMzkxN1owJDEQMA4G
A1UEAxMHV2ViIEVESTEQMA4GA1UEChMHV2ViIEVESTCCASIwDQYJKoZIhvcNAQEBBQADggEP
ADCCAQoCggEBANejtFteDrfVcsosbgerSLISkGZaomgRbqElAMzIoBt9auvPyiSzI893Ii1L
GIgPzer/YKnyHJ278fwqJ9xID5BP0ukAOXdrrtbMwWC1cgPsHAljfCgOWMl10Ry6wadp9myG
FV9z/WWI0eyXfSabQtTsiwZ9IX9EOBvJ1nrylB0WfIxz5aQc0WURyjtsEKFXMPdlF9xJezIj
tzlom82vD2VSSqukmsjBe5IQSeKm99U96TPHeQHs+JETdcWrGCd5ff3fXZ5QuPj9hNQdIjxA
JTJxMTKsqI4XSgqaXgq+5jaF5wv1FeA+ksQFGJyuoRslHsTnf94zyhjqY/iq1b8JaAkCAwEA
ATANBgkqhkiG9w0BAQUFAAOCAQEAIUDhP/IvGLzu0NJqkCtA4YSdmAtWCQFlEV8NrbJQjWRQ
Fbb88MMCxlXh/fs2PCISnpT9GyAbzCXFiA2v2aLoGqj1mSsaP9iIRiUNA9aJNVTWGEzkrGfk
P3+zA/1bquqyvPwzY6KZAIp18swV/cmB8HKzQT7Q252agNSVPp/YFwYT84FWVVlFtMmgLJbU
ROE7OEf7NAxOUOfirjU8JHgTJJNfJUbl2ma8nUqd+UKYG5NxsW6YnC+pBcWp66+h6do5vGLC
nx0D9QmPKky9nScaBit2VgSoOdRLrGo48ZaYNWs/hgPKPFM+hyXNBD+1A/h8b+vm8pQm2VKv
RnPyOoGNsA==
-----END CERTIFICATE-----
',
        'content_type' => 'application/EDI-Consent',
        'compression' => false,
        'signature_algorithm' => 'sha1',
        'encryption_algorithm' => '3des',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=required, pkcs7-signature; signed-receipt-micalg=optional, sha1',
    ],

    [
        'id' => 'EDI_AS2_OTTOGROUP',
        'target_url' => 'http://80.85.204.104:6060/as2connector/pub/ReceiveFile.rsb',
        'certificate' => '-----BEGIN CERTIFICATE-----
MIIDYTCCAkmgAwIBAgIDAnOgMA0GCSqGSIb3DQEBCwUAMHMxDTALBgNVBAMTBE9UVE8xDTAL
BgNVBAoTBE9UVE8xDTALBgNVBAsTBE9UVE8xEDAOBgNVBAcTB0hhbWJ1cmcxEDAOBgNVBAYT
B0dlcm1hbnkxIDAeBgkqhkiG9w0BCQEWEWVkaUBvdHRvZ3JvdXAuY29tMB4XDTIwMTExMjE0
MzI1M1oXDTIzMTExMjE0MzI1M1owczENMAsGA1UEAxMET1RUTzENMAsGA1UEChMET1RUTzEN
MAsGA1UECxMET1RUTzEQMA4GA1UEBxMHSGFtYnVyZzEQMA4GA1UEBhMHR2VybWFueTEgMB4G
CSqGSIb3DQEJARYRZWRpQG90dG9ncm91cC5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAw
ggEKAoIBAQDcTF5Qu6OZ9mE4OfdI8ok9WuRxNcCe5kXJd2pdeX7ufZDj8mTDQ1KKgoa9vpmY
SGMpX9vFFS0cJIFVguhBdo/CH3jPVmbQABItkEHYLJxYKr06QTo4rKWnRpf9H6zgiO8wdZ/B
F6iT79Hh9ogHI4N6woA7NjjF+q3612k1EPIekvZUJGxHiOhJjgbsPB0f+RlKPnW+lv7SwS4x
tq66XzrCaXJXjBQBeaEP31x/dVECGynjcynJhTAJYv+9XY6q0TFrqDsDhOmf9jTE5k1h/tML
y4A13cit2SHo0YN67mZwl+3cWP0h7cu1wF869HzyttPgWCG9/HHi5wLnU61aNxkhAgMBAAEw
DQYJKoZIhvcNAQELBQADggEBAEoA9sJPxINJilnowUWw0UmRYVSHjXQIuLdt+HmThSH+5K7x
TpwmD2OGmDAHPZ1wX/XNBuDye+ZoBWrybUhiPSTxN2K80N5Q4pJNcfS2LCw+m5qely0rfmZI
2AMOLJqB4M+nB5mxGWjMPAtBMtChcfpf5veOSLLw2aTb+/4Ek4c/6g3m8S2Uo+hdrt1EcLGC
06G7dcmG+ykziqGnQJRaC6pZy+Wxtytpp62kSOgPi72bA2BWOagTO5VxpoxbDtTdSNV3Qnfx
u+xcDrxvgWE8E3M0RyA4uTx3idhMLK0WZC4fbjQPDpIsB3YgIzWwtdQN566byEqmZ3uP2iuR
aHvMIGg=
-----END CERTIFICATE-----
',
        'content_type' => 'application/EDIFACT',
        'compression' => false,
        'signature_algorithm' => 'sha1',
        'encryption_algorithm' => '3des',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=required, pkcs7-signature; signed-receipt-micalg=optional, sha1',
    ],
    // local station

    [
        'id' => 'as2polini',
        'email' => 'phpas2@example.com',
        'target_url' => 'http://127.0.0.1:8000',
        'certificate' => '-----BEGIN CERTIFICATE-----
MIIC8TCCAdmgAwIBAgIJAI+A9000plXZMA0GCSqGSIb3DQEBCwUAMB0xGzAZBgNV
BAMMEmFzMi5ldGVjby1ncm91cC5kZTAeFw0xOTAxMTQwOTA1NThaFw0yOTAxMTEw
OTA1NThaMB0xGzAZBgNVBAMMEmFzMi5ldGVjby1ncm91cC5kZTCCASIwDQYJKoZI
hvcNAQEBBQADggEPADCCAQoCggEBAJZ/xA4MJPPA66Ils84DwklSBxim788LzFOs
i99RgO1ktfbbKFrdXIrEUYWvDmSMbiY7ALz9UsMKPA7T0/t0l1XkGuCh+/TqAQgb
MkjzNkCpjedufC9ghMSUhndSGIMdsQf70styWiZVSSNnZ4cG26H+mVJXKVcr5BRZ
ufv3fR+wMuADuGSE5xR5R+jhOLxgJEfvpZeuGKhGix6sdagE3MfjOH8vbtOmrblt
u8H9mbXPkiz9aSvEV3ocbesVIOxjhiWzUYvbYRhABebtNDKlvb7j3aBjoSHzaEhZ
OP4O+uSsoHOh0if0ukP3ksmHixEOVFzV8bPc92q3ONTH4ZNXFOkCAwEAAaM0MDIw
MAYDVR0RBCkwJ4ISYXMyLmV0ZWNvLWdyb3VwLmRlggtleGFtcGxlLm5ldIcECgAA
ATANBgkqhkiG9w0BAQsFAAOCAQEAOFHlT5n6IdH2xv6bi50OCwSPajVPz8hCAo6X
TdRwE5InaLVgziuRfQD1s/GUjLeM89u42CgA2FNkeKc4/iGvhCueFGMRjBlhHOEo
DwdcFpkLNJgtfaEmFDOHHjXgIP+MHbEQ7uu9Yspf+hdTDMT1CbCwRIWMfdt1VhGO
ZXoAg2Jgzwyqszf+H6EXilqZtzYdSDV4r2XoX+n2Oe6V3ootNdtsbh2QrtiTMS32
8brydAbXFsOzr2B/ygQkLPmEITgjyDqn2oXI8YR6Mfw0MImkGByapr7g+/eLHnuP
4ULqoQh54EkiTfodzbdRkhvT1cA9U+hH8BdPDB+jDsP5BWCcwg==
-----END CERTIFICATE-----
',
        'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCWf8QODCTzwOui
JbPOA8JJUgcYpu/PC8xTrIvfUYDtZLX22yha3VyKxFGFrw5kjG4mOwC8/VLDCjwO
09P7dJdV5Brgofv06gEIGzJI8zZAqY3nbnwvYITElIZ3UhiDHbEH+9LLclomVUkj
Z2eHBtuh/plSVylXK+QUWbn7930fsDLgA7hkhOcUeUfo4Ti8YCRH76WXrhioRose
rHWoBNzH4zh/L27Tpq25bbvB/Zm1z5Is/WkrxFd6HG3rFSDsY4Yls1GL22EYQAXm
7TQypb2+492gY6Eh82hIWTj+DvrkrKBzodIn9LpD95LJh4sRDlRc1fGz3PdqtzjU
x+GTVxTpAgMBAAECggEARd/0SwFgdrvvq00N+mzMW/Z1zQBU/zBfIcpO9tSEo7PK
uF5wkh+Mw/D6WLM6X3zD94QVh6mmL2AlGk1HcsxjJ0HNKNaMgN3UtMrLwgsJ+WO/
uuAVUHnjqtG6zNOVBetXMnm9GTByorGeT43HB24rsz7eONi3HP4H21r9evshYQBb
jqtlon/VGQcSWELVUNQg7i04ym0FvvmAZCYU085sZ2+3gaBtqwEHIH5DcX89Wf9I
3E3u2CrYWISPrzk4elBpjposnieM5ngxSi+WwUqkKxAsCO7VyEtG6IRgeip7mvIJ
NYn4LH6QYV2pAcHh2dGxv30bscxC4tvDDibVMJ8aAQKBgQDIqWO3FAMYpd/0d9Mt
f+0KFe2aiMz8Zxky1q01hhJuSA8d1OwyFUUklnJJWFs7BOLQ6JxMhBa9Ll5wAnPk
Yb504ee2k4titXMTjZYSJnh/TxNvLQq1+dz7bTR6cD/VY+sHcNp1ThJDyfaUxZZG
aNACc4E1U/Nf+y+5sZe4VTE/EQKBgQDAAOxcmYWuDOb4HMb9GsjEw5t6FUIY9ORB
YhdWh56iJDCXkxN9QvxSkm4R/4lorPtnWWOcA8XUJq6ti/D2pdRAFbFUKSzvXA+M
ZLUvkZmYT2c9NOV/tY6KTegxoGT9Rn0csw+rRqmU3bOAqX3xIedUfie29ze6gnzH
X/Mv4IWoWQKBgHc6DoGNZnmStYrwV43FYPaJKPCVMBcYuyQ14hzXWMQmFLVI+j6X
3MlsiuOBmFNtB8fRLm1YXppxnrM3Ad1FJoEUaTVWXY98+K85hV2rdhVOyuFYBfEy
UVci//dwEr2b7N4y89qXVMrqiZTEAhI73LxYHQGurADvot/W4aspE2XBAoGAfzsd
ZW9GKkPaeed35RjumZSVXpzfo/IDn2AE3w4XjJI2sPqBG6xbz8vArKSMxZR7M80E
OMo3OZI4hkAJeSgCMkUtsPtoD2UN2JaTieYNxeQ4IVMAEVSaFAP0LY5/3WXsWiw9
4d19WmxfGo82Kaexx0ehwZiokSsOzH9EgyFg8GECgYBBYtizJ2swvsyR+sMPoUEh
0hqCxCwEOJg3LetZ0/lWcdWQjuDyX+h0K/uyYo+TGL2hxPe3/Mi/POn70+5iNb9Q
2imhWJnyyQpU9rqGeIIWzcpqc7Du6S/sB84VwibT/YbWsMZW7KHCcLpWNwWfe0nJ
okbKQXNdhalu6KK2joUYMg==
-----END PRIVATE KEY-----
',
        'content_type' => 'application/EDI-Consent',
        'compression' => true,
        'signature_algorithm' => 'sha256',
        'encryption_algorithm' => '3des',
        'mdn_mode' => PartnerInterface::MDN_MODE_SYNC,
        'mdn_options' => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
    ],

    [
        'id' => 'phpas2',
        'email' => 'phpas2@example.com',
        'target_url' => 'http://127.0.0.1:8000',
        'certificate' => $local['cert'] ?? null,
        'private_key' => $local['pkey'] ?? null,
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
