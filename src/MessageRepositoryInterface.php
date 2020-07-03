<?php

namespace AS2;

interface MessageRepositoryInterface
{
    /**
     * @param string $id
     *
     * @return MessageInterface
     */
    public function findMessageById($id);

    /**
     * @param array $data
     *
     * @return MessageInterface
     */
    public function createMessage($data = []);

    /**
     * @return bool
     */
    public function saveMessage(MessageInterface $message);
}
