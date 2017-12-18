<?php

namespace AS2;

/**
 * Class for building and parsing AS2 Inbound and Outbound Messages
 * @package AS2
 */
interface MessageInterface
{
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_WARNING = 'warning';
    const STATUS_RETRY = 'retry';
    const STATUS_IN_PROCESS = 'in_process';

    /**
     * Unique Message Id
     * @return string
     */
    public function getUid();

    /**
     * @param string $id
     * @return $this
     */
    public function setUid($id);

    /**
     * @return PartnerInterface
     */
    public function getReceiver();

    /**
     * @param PartnerInterface $partner
     * @return $this
     */
    public function setReceiver(PartnerInterface $partner);

    /**
     * @return PartnerInterface
     */
    public function getSender();

    /**
     * @param PartnerInterface $partner
     * @return $this
     */
    public function setSender(PartnerInterface $partner);

    /**
     * @return string
     */
    public function getHeaders();

    /**
     * @param string $headers
     * @return $this
     */
    public function setHeaders($headers);

    /**
     * @return string
     */
    public function getBody();

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getMdn();

    /**
     * @param string $mdn
     * @return $this
     */
    public function setMdn($mdn);

    /**
     * @param string $mic
     * @return $this
     */
    public function setMic($mic);

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isSigned($val = null);

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isEncrypted($val = null);

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isCompressed($val = null);

}