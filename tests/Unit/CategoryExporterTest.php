<?php

namespace Webkul\Bagisto\Tests\Unit;

use Mockery;
use Tests\TestCase;
use Webkul\Bagisto\Helpers\Exporters\Category\Exporter;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CategoryFieldMappingRepository;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class CategoryExporterTest extends TestCase
{
    private $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new Exporter(
            Mockery::mock(JobTrackBatchRepository::class),
            Mockery::mock(FlatItemBuffer::class),
            Mockery::mock(BagistoDataMapping::class),
            Mockery::mock(CategoryFieldRepository::class),
            Mockery::mock(CategoryFieldMappingRepository::class),
            Mockery::mock(CredentialRepository::class),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_process_category_row_coerces_non_numeric_position_to_integer()
    {
        $data = $this->processRow(['position' => 'Mens']);

        $this->assertSame(1, $data['position']);
    }

    public function test_process_category_row_keeps_numeric_position()
    {
        $data = $this->processRow(['position' => '5']);

        $this->assertSame(5, $data['position']);
    }

    public function test_process_category_row_falls_back_display_mode_when_description_empty()
    {
        $data = $this->processRow(['display_mode' => 'products_and_description']);

        $this->assertSame('products', $data['display_mode']);
    }

    private function processRow(array $fixedValue): array
    {
        $this->setProperty($this->exporter, 'mappingFields', [
            'standard_field' => (object) ['mapped_value' => [], 'fixed_value' => $fixedValue],
        ]);

        $this->setProperty($this->exporter, 'credential', [
            'id'              => 1,
            'additional_info' => [['filterableAttribtes' => '1,2']],
        ]);

        $rowData = [
            'id'              => 1,
            'code'            => 'mens',
            'name'            => 'Mens',
            'additional_data' => ['common' => [], 'locale_specific' => []],
            'parent_category' => null,
        ];

        $method = new \ReflectionMethod($this->exporter, 'processCategoryRow');
        $method->setAccessible(true);

        return $method->invokeArgs($this->exporter, [$rowData, 'en', 'en_US', null]);
    }

    public function test_exportable_locales_keeps_well_formed_entries()
    {
        $this->setProperty($this->exporter, 'jobFilters', [
            'locales' => ['en' => 'en_US', 'de' => 'de_DE'],
        ]);

        $this->assertSame(['en' => 'en_US', 'de' => 'de_DE'], $this->exportableLocales());
    }

    public function test_exportable_locales_drops_an_entry_holding_an_array()
    {
        $this->setProperty($this->exporter, 'jobFilters', [
            'locales' => ['en' => 'en_US', 'default' => ['en' => 'en_US']],
        ]);

        $this->assertSame(['en' => 'en_US'], $this->exportableLocales());
    }

    public function test_exportable_locales_drops_non_string_entries()
    {
        $this->setProperty($this->exporter, 'jobFilters', [
            'locales' => ['en' => 'en_US', 'de' => 7, 'fr' => null],
        ]);

        $this->assertSame(['en' => 'en_US'], $this->exportableLocales());
    }

    public function test_exportable_locales_is_empty_when_the_mapping_is_missing()
    {
        $this->setProperty($this->exporter, 'jobFilters', []);

        $this->assertSame([], $this->exportableLocales());
    }

    public function test_prepare_categories_skips_every_row_when_no_locale_is_usable()
    {
        $this->setProperty($this->exporter, 'jobFilters', [
            'locales' => ['default' => ['en' => 'en_US']],
        ]);

        $batch = new class implements JobTrackBatchContract
        {
            public $data = [['code' => 'mens'], ['code' => 'womens']];
        };

        $this->assertSame([], $this->exporter->prepareCategories($batch, null));
        $this->assertSame(2, $this->exporter->getSkippedtemsCount());
    }

    private function exportableLocales(): array
    {
        $method = new \ReflectionMethod($this->exporter, 'getExportableLocales');
        $method->setAccessible(true);

        return $method->invoke($this->exporter);
    }

    private function setProperty(object $object, string $property, $value): void
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setAccessible(true);
        $ref->setValue($object, $value);
    }
}
