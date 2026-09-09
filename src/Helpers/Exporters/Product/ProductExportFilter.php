<?php

namespace Webkul\Bagisto\Helpers\Exporters\Product;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Bagisto\Enums\Export\ProductFilter as BagistoProductFilter;
use Webkul\Bagisto\Enums\Export\ProductStatus;
use Webkul\DataTransfer\Enums\ProductExportScope;
use Webkul\DataTransfer\Enums\ProductFilter;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;
use Webkul\DataTransfer\Helpers\Sources\Export\Filters\ProductExportFilter as BaseProductExportFilter;

class ProductExportFilter extends BaseProductExportFilter
{
    public function applyToQuery(Builder $query, array $filters): void
    {
        parent::applyToQuery($query, $filters);

        $this->applyType($query, $filters);
    }

    public function statusValue(array $filters): ?bool
    {
        return ProductStatus::tryFrom((string) ($filters[ProductFilter::STATUS->value] ?? ''))?->toBoolean();
    }

    protected function applyType(Builder $query, array $filters): void
    {
        $types = ScopeFilterValue::toCodes($filters[BagistoProductFilter::TYPE->value] ?? null);

        if ($types === []) {
            return;
        }

        $query->whereIn('type', $types);
    }

    protected function resolveChannelIds(array $filters): array
    {
        return parent::resolveChannelIds($this->toCoreScope($filters));
    }

    protected function resolveLocaleIds(array $filters): array
    {
        return parent::resolveLocaleIds($this->toCoreScope($filters));
    }

    protected function toCoreScope(array $filters): array
    {
        return [
            ProductExportScope::CHANNELS->value => $filters[BagistoProductFilter::CHANNEL->value] ?? null,
            ProductExportScope::LOCALES->value  => $filters[BagistoProductFilter::LOCALE->value] ?? null,
        ];
    }
}
