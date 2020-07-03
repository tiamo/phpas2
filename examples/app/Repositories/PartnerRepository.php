<?php

namespace App\Repositories;

use AS2\PartnerRepositoryInterface;

class PartnerRepository implements PartnerRepositoryInterface
{
    private $partners;

    public function __construct(array $partners)
    {
        $this->partners = $partners;
    }

    public function findPartnerById($id)
    {
        return $this->partners[$id];
    }
}
