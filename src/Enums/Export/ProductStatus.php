<?php

namespace Webkul\Bagisto\Enums\Export;

enum ProductStatus: string
{
    case ALL = 'all';

    case ENABLED = 't';

    case DISABLED = 'f';

    public function toBoolean(): ?bool
    {
        return match ($this) {
            self::ENABLED  => true,
            self::DISABLED => false,
            self::ALL      => null,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
