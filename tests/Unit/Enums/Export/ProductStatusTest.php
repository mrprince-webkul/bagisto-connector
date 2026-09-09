<?php

namespace Webkul\Bagisto\Tests\Unit\Enums\Export;

use Tests\TestCase;
use Webkul\Bagisto\Enums\Export\ProductStatus;

class ProductStatusTest extends TestCase
{
    public function test_to_boolean_maps_every_case()
    {
        $this->assertTrue(ProductStatus::ENABLED->toBoolean());
        $this->assertFalse(ProductStatus::DISABLED->toBoolean());
        $this->assertNull(ProductStatus::ALL->toBoolean());
    }

    public function test_values_lists_the_selectable_filter_values()
    {
        $this->assertSame(['all', 't', 'f'], ProductStatus::values());
    }

    public function test_try_from_rejects_a_core_status_value()
    {
        $this->assertNull(ProductStatus::tryFrom('enable'));
    }
}
