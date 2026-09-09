<?php

namespace Webkul\Bagisto\Enums\Export;

enum ProductType: string
{
    case SIMPLE = 'simple';

    case CONFIGURABLE = 'configurable';

    case VARIANT_GROUP = 'variant_group';
}
