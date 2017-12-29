<?php

namespace AS2;

interface MessageInterface
{
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_WARNING = 'warning';
    const STATUS_RETRY = 'retry';
    const STATUS_IN_PROCESS = 'in_process';

    const MDN_STATUS_PENDING = 'pending';
    const MDN_STATUS_RECEIVED = 'received';
    const MDN_STATUS_SENT = 'sent';
    const MDN_STATUS_ERROR = 'error';

    const DIR_INBOUND = 1;
    const DIR_OUTBOUND = 0;

    /**
     * Unique Message Id
     * @return string
     */
    public function getMessageId();

    /**
     * @param string $id
     * @return $this
     */
    public function setMessageId($id);

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
     * @return PartnerInterface
     */
    public function getReceiver();

    /**
     * @param PartnerInterface $partner
     * @return $this
     */
    public function setReceiver(PartnerInterface $partner);

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
    public function getPayload();

    /**
     * @param string $payload
     * @return $this
     */
    public function setPayload($payload);

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
    public function getStatusMsg();

    /**
     * @param string $msg
     * @return $this
     */
    public function setStatusMsg($msg);

    /**
     * @return string
     */
    public function getMdnStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setMdnStatus($status);

    /**
     * @return string
     */
    public function getMdnPayload();

    /**
     * @param mixed $mdn
     * @return $this
     */
    public function setMdnPayload($mdn);

    /**
     * @return string
     */
    public function getCalculatedMic();

    /**
     * @param string $mic
     * @return $this
     */
    public function setCalculatedMic($mic);

    /**
     * @return bool
     */
    public function getSigned();

    /**
     * @param bool $val
     * @return $this
     */
    public function setSigned($val = true);

    /**
     * @return bool
     */
    public function getEncrypted();

    /**
     * @param bool $val
     * @return $this
     */
    public function setEncrypted($val = true);

    /**
     * @return bool
     */
    public function getCompressed();

    /**
     * @param bool $val
     * @return $this
     */
    public function setCompressed($val = true);
}
