<?php

namespace Webkul\Bagisto\Listeners;

use Illuminate\Support\Facades\Cache;
use Webkul\Bagisto\Enums\Export\CacheType;

class CategoryField
{
    public function afterCreateOrUpdate(): void
    {
        Cache::forget(CacheType::UNOPIM_CATEGORY_FIELDS->value);
    }
}
