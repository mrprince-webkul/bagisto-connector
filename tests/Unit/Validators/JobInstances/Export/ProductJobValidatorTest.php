<?php

namespace Webkul\Bagisto\Tests\Unit\Validators\JobInstances\Export;

use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Webkul\Bagisto\Validators\JobInstances\Export\ProductJobValidator;

class ProductJobValidatorTest extends TestCase
{
    private ProductJobValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ProductJobValidator;
    }

    public function test_it_accepts_the_bagisto_status_values()
    {
        foreach (['all', 't', 'f'] as $status) {
            $this->validate(['status' => $status]);
        }

        $this->expectNotToPerformAssertions();
    }

    public function test_it_rejects_a_core_status_value()
    {
        $this->expectException(ValidationException::class);

        $this->validate(['status' => 'enable']);
    }

    public function test_it_rejects_a_non_boolean_media_flag()
    {
        $this->expectException(ValidationException::class);

        $this->validate(['with_media' => '2']);
    }

    public function test_it_accepts_the_boolean_export_flags()
    {
        $this->validate(['with_media' => '1', 'with_associations' => '0']);

        $this->expectNotToPerformAssertions();
    }

    public function test_it_requires_the_number_of_days_for_the_last_n_days_condition()
    {
        $this->expectException(ValidationException::class);

        $this->validate(['time_condition' => 'last_n_days']);
    }

    public function test_it_rejects_an_end_date_earlier_than_the_start_date()
    {
        $this->expectException(ValidationException::class);

        $this->validate([
            'time_condition' => 'between_dates',
            'time_date'      => '2026-02-20',
            'time_date_end'  => '2026-01-15',
        ]);
    }

    private function validate(array $filters): void
    {
        $this->validator->validate(['filters' => $filters]);
    }
}
