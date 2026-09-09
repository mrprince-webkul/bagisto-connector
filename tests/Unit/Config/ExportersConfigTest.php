<?php

namespace Webkul\Bagisto\Tests\Unit\Config;

use Tests\TestCase;
use Webkul\Bagisto\Enums\Export\ProductFilter;
use Webkul\Bagisto\Enums\Export\ProductStatus;
use Webkul\Bagisto\Validators\JobInstances\Export\ProductJobValidator;

class ExportersConfigTest extends TestCase
{
    public function test_every_exporter_points_at_a_class_that_exists()
    {
        foreach ($this->bagistoExporters() as $key => $exporter) {
            foreach (['exporter', 'source', 'validator'] as $slot) {
                if (isset($exporter[$slot])) {
                    $this->assertTrue(class_exists($exporter[$slot]), "{$key}.{$slot} is missing");
                }
            }
        }
    }

    public function test_the_product_export_is_validated()
    {
        $this->assertSame(ProductJobValidator::class, config('exporters.bagisto_product.validator'));
    }

    public function test_the_scoped_exports_share_the_connector_filters()
    {
        foreach (['bagisto_categories', 'bagisto_attribute', 'bagisto_attribute_families'] as $key) {
            $this->assertSame(
                ['credentials', 'channel', 'locale', 'code'],
                array_column(config("exporters.{$key}.filters.fields"), 'name')
            );
        }
    }

    public function test_the_status_filter_offers_the_bagisto_status_values()
    {
        $this->assertSame(ProductStatus::values(), array_column($this->productField('status')['options'], 'value'));
    }

    public function test_identifier_filters_are_tag_inputs()
    {
        $this->assertSame('tags', $this->productField('sku')['type']);
        $this->assertSame('tags', config('exporters.bagisto_categories.filters.fields.3.type'));
    }

    public function test_connector_fields_match_the_filters_the_product_export_declares()
    {
        $names = array_column(config('exporters.bagisto_product.filters.fields'), 'name');

        foreach (array_diff(ProductFilter::connectorFields(), ['code']) as $field) {
            $this->assertContains($field, $names);
        }
    }

    private function bagistoExporters(): array
    {
        return array_filter(
            config('exporters'),
            fn ($key): bool => str_starts_with($key, 'bagisto_'),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function productField(string $name): array
    {
        $fields = config('exporters.bagisto_product.filters.fields');

        return collect($fields)->firstWhere('name', $name) ?? [];
    }
}
