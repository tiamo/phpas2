<?php

namespace AS2\Tests\Mock;

use AS2\MessageInterface;
use AS2\PartnerInterface;
use AS2\StorageInterface;

class FileStorage implements StorageInterface
{
    const TYPE_MESSAGE = 'message';
    const TYPE_PARTNER = 'partner';

    /**
     * @param array $data
     * @return Message
     */
    public function initMessage($data = [])
    {
        return new Message($data);
    }

    /**
     * @param string $id
     * @return MessageInterface|false
     */
    public function getMessage($id)
    {
        $path = $this->getFile(self::TYPE_MESSAGE, $id);
        if (file_exists($path)) {
            $message = new Message(json_decode(file_get_contents($path), true));
            $message->setSender($this->getPartner($message->getSenderId()));
            $message->setReceiver($this->getPartner($message->getReceiverId()));
            return $message;
        }
        return false;
    }

    /**
     * @param Message|MessageInterface $message
     * @return bool
     */
    public function saveMessage(MessageInterface $message)
    {
        $data = $message->getData();
        unset($data['receiver'], $data['receiver']);

        $path = $this->getFile(self::TYPE_MESSAGE, $message->getMessageId());

        if ($headers = $message->getHeaders()) {
            file_put_contents(str_replace('.json', '.headers', $path), $headers);
        }

        if ($payload = $message->getPayload()) {
            file_put_contents(str_replace('.json', '.payload', $path), $payload);
        }

        if ($mdn = $message->getMdnPayload()) {
            file_put_contents(str_replace('.json', '.mdn', $path), $mdn);
        }

        return (bool)file_put_contents($path, json_encode($message->getData()));
    }

    /**
     * @param array $data
     * @return PartnerInterface
     */
    public function initPartner($data = [])
    {
        return new Partner($data);
    }

    /**
     * @param string $id
     * @return PartnerInterface|false
     */
    public function getPartner($id)
    {
        $path = $this->getFile(self::TYPE_PARTNER, $id);
        if (file_exists($path)) {
            return new Partner(json_decode(file_get_contents($path), true));
        }
        return false;
    }

    /**
     * @param PartnerInterface|Partner $partner
     * @return bool
     */
    public function savePartner(PartnerInterface $partner)
    {
        $path = $this->getFile(self::TYPE_PARTNER, $partner->getAs2Id());
        return (bool)file_put_contents($path, json_encode($partner->getData()));
    }

    /**
     * @param string $type
     * @param string $id
     * @param string $format
     * @return string
     */
    protected function getFile($type, $id, $format = 'json')
    {
        $basePath = realpath(__DIR__ . '/../resources');
        return $basePath . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . strtolower($id) . '.' . $format;
    }
}