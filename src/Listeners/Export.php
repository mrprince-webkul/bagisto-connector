<?php

namespace Webkul\Bagisto\Listeners;

use Illuminate\Support\Facades\Cache;
use Webkul\Bagisto\Enums\Export\CacheType;

class Export
{
    public function afterUpdate($export): void
    {
        $types = [
            'bagisto_product',
            'bagisto_categories',
            'bagisto_attribute',
            'bagisto_attribute_families',
        ];

        if (in_array($export->entity_type, $types)) {
            Cache::forget(CacheType::CREDENTIAL->value);
            Cache::forget(CacheType::PRODUCT_JOB_FILTERS->value);
            Cache::forget(CacheType::CATEGORY_JOB_FILTERS->value);
            Cache::forget(CacheType::ADDITIONAL_INFO->value);
        }
    }
}
