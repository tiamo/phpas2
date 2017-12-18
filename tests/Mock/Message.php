<?php

namespace AS2\Tests\Mock;

use AS2\MessageInterface;
use AS2\PartnerInterface;

class Message extends DataObject implements MessageInterface
{
    /**
     * Unique Message Id
     * @return string
     */
    public function getUid()
    {
        return $this->getData('id');
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setUid($id)
    {
        return $this->setData('id', $id);
    }

    /**
     * @return PartnerInterface
     */
    public function getSender()
    {
        return $this->getData('sender');
    }

    /**
     * @param PartnerInterface $partner
     * @return $this
     */
    public function setSender(PartnerInterface $partner)
    {
        return $this->setData('sender', $partner);
    }

    /**
     * @return PartnerInterface
     */
    public function getReceiver()
    {
        return $this->getData('receiver');
    }

    /**
     * @param PartnerInterface $partner
     * @return $this
     */
    public function setReceiver(PartnerInterface $partner)
    {
        return $this->setData('receiver', $partner);
    }

    /**
     * @return string
     */
    public function getHeaders()
    {
        return $this->getData('headers');
    }

    /**
     * @param string $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        return $this->setData('headers', $headers);
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->getData('body');
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        return $this->setData('body', $body);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * @return string
     */
    public function getMdn()
    {
        return $this->getData('mdn');
    }

    /**
     * @param string $mdn
     * @return $this
     */
    public function setMdn($mdn)
    {
        return $this->setData('mdn', $mdn);
    }

    /**
     * @param string $mic
     * @return $this
     */
    public function setMic($mic)
    {
        return $this->setData('mic', $mic);
    }

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isSigned($val = null)
    {
        return $val ? $this->setData('signed', $val) : $this->getData('signed');
    }

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isEncrypted($val = null)
    {
        return $val ? $this->setData('encrypted', $val) : $this->getData('encrypted');
    }

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isCompressed($val = null)
    {
        return $val ? $this->setData('compressed', $val) : $this->getData('compressed');
    }
}
