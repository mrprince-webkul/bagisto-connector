<?php

namespace Webkul\Bagisto\Repositories;

use Webkul\Core\Eloquent\Repository;

class AttributeMappingRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Bagisto\Contracts\AttributeMapping';
    }
}
