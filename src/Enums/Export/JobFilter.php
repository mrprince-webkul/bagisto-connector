<?php

namespace Webkul\Bagisto\Enums\Export;

/**
 * Keys of the resolved filter set the product exporter caches between batches.
 * Distinct from ProductFilter, which names the fields the export profile stores.
 */
enum JobFilter: string
{
    case WITH_MEDIA = 'withMedia';

    case WITH_ASSOCIATIONS = 'withAssociations';

    case CHANNEL = 'channel';

    case LOCALES = 'locales';
}
