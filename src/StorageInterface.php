<?php

namespace AS2;

interface StorageInterface
{
    /**
     * @param array $data
     *
     * @return MessageInterface
     */
    public function initMessage($data = []);

    /**
     * @param string $id
     *
     * @return MessageInterface
     */
    public function getMessage($id);

    /**
     * @return bool
     */
    public function saveMessage(MessageInterface $message);

    /**
     * @param array $data
     *
     * @return PartnerInterface
     */
    public function initPartner($data = []);

    /**
     * @param string $id
     *
     * @return PartnerInterface
     */
    public function getPartner($id);

    /**
     * @return bool
     */
    public function savePartner(PartnerInterface $partner);
}
