<?php

namespace AS2\Tests\Mock;

use AS2\MessageInterface;
use AS2\PartnerInterface;

class Message extends DataObject implements MessageInterface
{
    /**
     * Unique Message Id.
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->getData('id');
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setMessageId($id)
    {
        return $this->setData('id', $id);
    }

    /**
     * @param string $dir
     *
     * @return $this
     */
    public function setDirection($dir)
    {
        return $this->setData('direction', $dir);
    }

    /**
     * @return string
     */
    public function getDirection()
    {
        return $this->getData('direction');
    }

    /**
     * @param string $id
     *
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
     * @return $this
     */
    public function setSender(PartnerInterface $partner)
    {
        $this->setSenderId($partner->getAs2Id());

        return $this->setData('sender', $partner);
    }

    /**
     * @param string $id
     *
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
     *
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
     *
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
     *
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * @return string
     */
    public function getStatusMsg()
    {
        return $this->getData('status_msg');
    }

    /**
     * @param string $msg
     *
     * @return $this
     */
    public function setStatusMsg($msg)
    {
        return $this->setData('status_msg', $msg);
    }

    /**
     * @return string
     */
    public function getMdnMode()
    {
        return $this->getData('mdn_mode');
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setMdnMode($status)
    {
        return $this->setData('mdn_mode', $status);
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
     *
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
     *
     * @return $this
     */
    public function setMdnPayload($mdn)
    {
        return $this->setData('mdn', $mdn);
    }

    /**
     * @return string
     */
    public function getMic()
    {
        return $this->getData('mic');
    }

    /**
     * @param string $mic
     *
     * @return $this
     */
    public function setMic($mic)
    {
        return $this->setData('mic', $mic);
    }

    /**
     * @return bool
     */
    public function getSigned()
    {
        return $this->getData('signed');
    }

    /**
     * @param bool $val
     *
     * @return $this
     */
    public function setSigned($val = true)
    {
        return $this->setData('signed', $val);
    }

    /**
     * @return bool
     */
    public function getEncrypted()
    {
        return $this->getData('encrypted');
    }

    /**
     * @param bool $val
     *
     * @return $this
     */
    public function setEncrypted($val = true)
    {
        return $this->setData('encrypted', $val);
    }

    /**
     * @return bool
     */
    public function getCompressed()
    {
        return $this->getData('compressed');
    }

    /**
     * @param bool $val
     *
     * @return $this
     */
    public function setCompressed($val = true)
    {
        return $this->setData('compressed', $val);
    }
}
