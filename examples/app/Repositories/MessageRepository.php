<?php

namespace App\Repositories;

use AS2\MessageInterface;
use AS2\MessageRepositoryInterface;
use App\Models\Message;

class MessageRepository implements MessageRepositoryInterface
{
    protected $format = 'json';
    protected $path;

    public function __construct(array $options)
    {
        if (empty($options['path'])) {
            throw new \RuntimeException('`path` required');
        }
        if (isset($options['format'])) {
            $this->format = $options['format'];
        }
    }

    /**
     * @param  string  $id
     * @return Message
     */
    public function findMessageById($id)
    {
        $data = file_get_contents(
            sprintf('%s/messages/%s.%s', $this->path, $id, $this->format)
        );

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

        if ($headers = $message->getHeaders()) {
            file_put_contents(str_replace('.json', '.headers', $this->path), $headers);
        }

        if ($payload = $message->getPayload()) {
            file_put_contents(str_replace('.json', '.payload', $this->path), $payload);
        }

        if ($mdn = $message->getMdnPayload()) {
            file_put_contents(str_replace('.json', '.mdn', $this->path), $mdn);
        }

        if ($headers = $message->getHeaders()) {
            file_put_contents(str_replace('.json', '.txt', $this->path), $headers.PHP_EOL.$payload);
        }

        return file_put_contents($this->path, json_encode($data));
    }
}
