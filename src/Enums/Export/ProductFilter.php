<?php

namespace Webkul\Bagisto\Enums\Export;

enum ProductFilter: string
{
    case CREDENTIALS = 'credentials';

    case CHANNEL = 'channel';

    case LOCALE = 'locale';

    case TYPE = 'type';

    case CODE = 'code';

    case WITH_MEDIA = 'with_media';

    case WITH_ASSOCIATIONS = 'with_associations';

    /**
     * Fields the connector renders in its own filter card, ahead of the core
     * ones. Shared by the create and edit export views.
     */
    public static function connectorFields(): array
    {
        return array_column([
            self::CREDENTIALS,
            self::CHANNEL,
            self::LOCALE,
            self::TYPE,
            self::CODE,
        ], 'value');
    }
}
