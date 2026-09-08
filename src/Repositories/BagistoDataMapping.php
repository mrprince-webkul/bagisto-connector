<?php

namespace Webkul\Bagisto\Repositories;

use Webkul\Core\Eloquent\Repository;

class BagistoDataMapping extends Repository
{
    public function model(): string
    {
        return 'Webkul\Bagisto\Contracts\BagistoDataMapping';
    }
}
