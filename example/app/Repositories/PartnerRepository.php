<?php


namespace App\Repositories;

use App\Models\Partner;
use AS2\PartnerRepositoryInterface;

class PartnerRepository implements PartnerRepositoryInterface
{
    /**
     * @var array
     */
    private $partners;

    public function __construct(array $partners)
    {
        $this->partners = $partners;
    }

    /**
     * @param  string  $id
     *
     * @return Partner
     */
    public function findPartnerById($id)
    {
        foreach ($this->partners as $partner) {
            if ($id === $partner['id']) {
                return new Partner($partner);
            }
        }

        throw new \RuntimeException(sprintf('Unknown partner `%s`.', $id));
    }
}
