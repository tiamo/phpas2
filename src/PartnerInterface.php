<?php

namespace AS2;

interface PartnerInterface
{
    const MDN_MODE_SYNC = 'sync';
    const MDN_MODE_ASYNC = 'async';

    /**
     * Partner Unique Identifier
     * @return string
     */
    public function getAs2Id();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getTargetUrl();

    /**
     * @return string
     */
    public function getContentType();

    /**
     * @return string
     */
    public function getSubject();

    /**
     * @return string|null
     */
    public function getAuthMethod();

    /**
     * @return string
     */
    public function getAuthUser();

    /**
     * @return string
     */
    public function getAuthPassword();

    /**
     * @return string|null
     */
    public function getSignatureAlgorithm();

    /**
     * @return string|null
     */
    public function getEncryptionAlgorithm();

    /**
     * @return string
     */
    public function getPublicKey();

    /**
     * @return string
     */
    public function getPrivateKey();

    /**
     * @return string
     */
    public function getPrivateKeyPassPhrase();

    /**
     * @return string [null, zlib, deflate]
     */
    public function getCompressionType();

    /**
     * @return string [null, sync, async]
     */
    public function getMdnMode();

    /**
     * @return string (Example: signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, SHA256)
     */
    public function getMdnOptions();

    /**
     * @return string (Example: Your requested MDN response from $receiver.as2_id$)
     */
    public function getMdnSubject();

}