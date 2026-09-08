<?php

namespace Webkul\Bagisto\Providers;

use Webkul\Bagisto\Models\AttributeMapping;
use Webkul\Bagisto\Models\BagistoDataMapping;
use Webkul\Bagisto\Models\CategoryFieldMapping;
use Webkul\Bagisto\Models\Credential;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Credential::class,
        AttributeMapping::class,
        CategoryFieldMapping::class,
        BagistoDataMapping::class,
    ];
}
