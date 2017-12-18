<?php

namespace AS2\Tests\Mock;

use AS2\PartnerInterface;

class Partner extends DataObject implements PartnerInterface
{
    /**
     * Unique Message Id
     * @return string
     */
    public function getUid()
    {
        return $this->getData('id');
    }

    public function getTargetUrl()
    {
        return $this->getData('target_url');
    }

    public function getContentType()
    {
        return $this->getData('content_type');
    }

    public function getSubject()
    {
        return $this->getData('subject');
    }

    /**
     * @return string|null
     */
    public function getAuthMethod()
    {
        return $this->getData('auth_method');
    }

    public function getAuthUser()
    {
        return $this->getData('auth_user');
    }

    public function getAuthPassword()
    {
        return $this->getData('auth_password');
    }

    /**
     * @return string|null
     */
    public function getSignatureAlgorithm()
    {
        return $this->getData('sign_algo');
    }

    public function getSignatureKey()
    {
        return $this->getData('sign_key');
    }

    /**
     * @return string|null
     */
    public function getEncryptionAlgorithm()
    {
        return $this->getData('enc_algo');
    }

    public function getEncryptionKey()
    {
        return $this->getData('enc_key');
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->getData('public_key');
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
}
