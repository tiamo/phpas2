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
    public function getMessageId()
    {
        return $this->getData('id');
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setMessageId($id)
    {
        return $this->setData('id', $id);
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setSenderId($id)
    {
        return $this->setData('sender_id', $id);
    }

    /**
     * @return string
     */
    public function getSenderId()
    {
        return $this->getData('sender_id');
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
        $this->setSenderId($partner->getAs2Id());
        return $this->setData('sender', $partner);
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setReceiverId($id)
    {
        return $this->setData('receiver_id', $id);
    }

    /**
     * @return string
     */
    public function getReceiverId()
    {
        return $this->getData('receiver_id');
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
        $this->setReceiverId($partner->getAs2Id());
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
    public function getPayload()
    {
        return $this->getData('payload');
    }

    /**
     * @param string $payload
     * @return $this
     */
    public function setPayload($payload)
    {
        return $this->setData('payload', $payload);
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
    public function getMdnStatus()
    {
        return $this->getData('mdn_status');
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setMdnStatus($status)
    {
        return $this->setData('mdn_status', $status);
    }

    /**
     * @return string
     */
    public function getMdnPayload()
    {
        return $this->getData('mdn');
    }

    /**
     * @param mixed $mdn
     * @return $this
     */
    public function setMdnPayload($mdn)
    {
        return $this->setData('mdn', $mdn);
    }

    /**
     * @return string
     */
    public function getCalculatedMic()
    {
        return $this->getData('mic');
    }

    /**
     * @param string $mic
     * @return $this
     */
    public function setCalculatedMic($mic)
    {
        return $this->setData('mic', $mic);
    }

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isSigned($val = null)
    {
        return $val ? $this->setData('signed', $val) : (bool)$this->getData('signed');
    }

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isEncrypted($val = null)
    {
        return $val ? $this->setData('encrypted', $val) : (bool)$this->getData('encrypted');
    }

    /**
     * @param string $val
     * @return bool|$this
     */
    public function isCompressed($val = null)
    {
        return $val ? $this->setData('compressed', $val) : (bool)$this->getData('compressed');
    }
}
