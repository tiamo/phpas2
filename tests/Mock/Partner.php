<?php

namespace AS2\Tests\Mock;

use AS2\PartnerInterface;

class Partner extends DataObject implements PartnerInterface
{
    /**
     * AS2 Partner ID.
     *
     * @return string
     */
    public function getAs2Id()
    {
        return $this->getData('id');
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * @return string
     */
    public function getTargetUrl()
    {
        return $this->getData('target_url');
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->getData('content_type');
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->getData('subject');
    }

    /**
     * @return string|null
     */
    public function getAuthMethod()
    {
        return $this->getData('auth');
    }

    /**
     * @return string
     */
    public function getAuthUser()
    {
        return $this->getData('auth_user');
    }

    /**
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->getData('auth_password');
    }

    /**
     * @return string|null
     */
    public function getSignatureAlgorithm()
    {
        return $this->getData('signature_algorithm');
    }

    /**
     * @return string|null
     */
    public function getEncryptionAlgorithm()
    {
        return $this->getData('encryption_algorithm');
    }

    /**
     * @return string
     */
    public function getCertificate()
    {
        return $this->getData('certificate');
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->getData('private_key');
    }

    /**
     * @return string
     */
    public function getPrivateKeyPassPhrase()
    {
        // TODO: Implement getPrivateKeyPassPhrase() method.
        return $this->getData('private_key_pass_phrase');
    }

    /**
     * @return string [null, zlib, deflate]
     */
    public function getCompressionType()
    {
        return $this->getData('compression');
    }

    /**
     * @return string [null, sync, async]
     */
    public function getMdnMode()
    {
        return $this->getData('mdn_mode');
    }

    /**
     * @return string (Example: signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256)
     */
    public function getMdnOptions()
    {
        return $this->getData('mdn_options');
    }

    /**
     * @return string (Example: Your requested MDN response from $receiver.as2_id$)
     */
    public function getMdnSubject()
    {
        return $this->getData('mdn_subject');
    }

    /**
     * @return string
     */
    public function getContentTransferEncoding()
    {
        return $this->getData('content_transfer_encoding');
    }
}
