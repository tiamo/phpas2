<?php

namespace AS2\Tests\Mock;

use AS2\MessageInterface;
use AS2\PartnerInterface;
use AS2\StorageInterface;

class Storage implements StorageInterface
{
    /**
     * @return string
     */
    public function getBasePath()
    {
        return __DIR__ . '/../resources';
    }

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
    public function getMessageById($id)
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'messages' . DIRECTORY_SEPARATOR . strtolower($id) . '.txt';
        if (file_exists($path)) {
            return new Message(json_decode(file_get_contents($path), true));
        }
        return false;
    }

    /**
     * @param MessageInterface $message
     * @return bool
     */
    public function saveMessage(MessageInterface $message)
    {
        return file_put_contents(
            $this->getBasePath() . DIRECTORY_SEPARATOR . 'messages' . DIRECTORY_SEPARATOR . strtolower($message->getUid()) . '.txt',
            json_encode($message->getData())
        );
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
    public function getPartnerById($id)
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'partners' . DIRECTORY_SEPARATOR . strtolower($id) . '.txt';
        if (file_exists($path)) {
            return new Partner(json_decode(file_get_contents($path), true));
        }
        return false;
    }

    /**
     * @param PartnerInterface $partner
     * @return bool
     */
    public function savePartner(PartnerInterface $partner)
    {
        return file_put_contents(
            $this->getBasePath() . DIRECTORY_SEPARATOR . 'partners' . DIRECTORY_SEPARATOR . strtolower($partner->getUid()) . '.txt',
            json_encode($partner->getData())
        );
    }
}