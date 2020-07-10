<?php

namespace AS2\Tests\Mock;

use AS2\PartnerInterface;
use AS2\PartnerRepositoryInterface;

class PartnerRepository implements PartnerRepositoryInterface
{
    private $partners;

    public function __construct()
    {
        openssl_pkcs12_read(
            file_get_contents(__DIR__ . '/../fixtures/phpas2.p12'),
            $key,
            null
        );

        $this->partners = [
            [
                'id' => 'A',
                // 'target_url' => 'php://memory',
                'private_key'               => isset($key['pkey']) ? $key['pkey'] : null,
                'certificate'               => isset($key['cert']) ? $key['cert'] : null,
                'content_type'              => 'application/EDI-Consent',
                'compression'               => true,
                'signature_algorithm'       => 'sha256',
                'encryption_algorithm'      => '3des',
                'content_transfer_encoding' => 'binary',
                'mdn_mode'                  => PartnerInterface::MDN_MODE_SYNC,
                'mdn_options'               => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
            ],
            [
                'id' => 'B',
                // 'target_url' => 'php://memory',
                'private_key'               => isset($key['pkey']) ? $key['pkey'] : null,
                'certificate'               => isset($key['cert']) ? $key['cert'] : null,
                'content_type'              => 'application/EDI-Consent',
                'compression'               => true,
                'signature_algorithm'       => 'sha256',
                'encryption_algorithm'      => '3des',
                'content_transfer_encoding' => 'binary',
                'mdn_mode'                  => PartnerInterface::MDN_MODE_SYNC,
                'mdn_options'               => 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha256',
            ],
        ];
    }

    public function findPartnerById($id)
    {
        foreach ($this->partners as $partner) {
            if ($id === $partner['id']) {
                return new Partner($partner);
            }
        }

        throw new \RuntimeException(sprintf('Unknown partner `%s`.', $id));
    }
}
