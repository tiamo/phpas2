<?php

namespace AS2\Tests\Mock;

use AS2\MessageInterface;
use AS2\MessageRepositoryInterface;

class MessageRepository implements MessageRepositoryInterface
{
    /**
     * @param string $id
     *
     * @return Message
     */
    public function findMessageById($id)
    {
        return null;
    }

    public function createMessage($data = [])
    {
        return new Message($data);
    }

    /**
     * @param MessageInterface|Message $message
     *
     * @return bool
     */
    public function saveMessage(MessageInterface $message)
    {
        return true;
    }
}
