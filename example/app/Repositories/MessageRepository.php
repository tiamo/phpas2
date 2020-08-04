<?php

namespace App\Repositories;

use AS2\MessageInterface;
use AS2\MessageRepositoryInterface;
use App\Models\Message;

class MessageRepository implements MessageRepositoryInterface
{
    protected $path;

    public function __construct(array $options)
    {
        if (empty($options['path'])) {
            throw new \RuntimeException('`path` required');
        }

        $this->path = $options['path'];
    }

    /**
     * @param  string  $id
     * @return Message
     */
    public function findMessageById($id)
    {
        $path = sprintf('%s/%s.json', $this->path, $id);
        if (! file_exists($path)) {
            return null;
        }

        $data = file_get_contents($path);
        if (empty($data)) {
            return null;
        }

        return new Message(json_decode($data, true));
    }

    public function createMessage($data = [])
    {
        return new Message($data);
    }

    /**
     * @param  MessageInterface|Message  $message
     * @return bool
     */
    public function saveMessage(MessageInterface $message)
    {
        $data = $message->getData();
        unset($data['receiver'], $data['receiver']);

        $path = sprintf('%s/%s', $this->path, $message->getMessageId());

        // if ($headers = $message->getHeaders()) {
        //     file_put_contents($path.'.headers', $headers);
        // }
        //
        // if ($payload = $message->getPayload()) {
        //     file_put_contents($path.'.payload', $payload);
        // }
        //
        // if ($mdn = $message->getMdnPayload()) {
        //     file_put_contents($path.'.mdn', $mdn);
        // }

        return (bool) file_put_contents($path.'.json', json_encode($data));
    }
}
