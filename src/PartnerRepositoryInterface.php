<?php

namespace AS2;

interface PartnerRepositoryInterface
{
    /**
     * @param string $id
     *
     * @return PartnerInterface
     */
    public function findPartnerById($id);
}
