<?php

namespace Webkul\Bagisto\Tests\Unit;

use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Helpers\Exporters\Product\Exporter;
use Webkul\Bagisto\Repositories\AttributeMappingRepository;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;

class ExporterTest extends TestCase
{
    private $exporter;

    private $jobLogger;

    private $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $batchRepo = Mockery::mock(JobTrackBatchRepository::class);
        $fileBuffer = Mockery::mock(FlatItemBuffer::class);
        $bagistoMapping = Mockery::mock(BagistoDataMapping::class);
        $attrRepo = Mockery::mock(AttributeRepository::class);
        $prodRepo = $this->productRepository = Mockery::mock(ProductRepository::class);
        $catRepo = Mockery::mock(CategoryRepository::class);
        $attrOptionRepo = Mockery::mock(AttributeOptionRepository::class);
        $attrMappingRepo = Mockery::mock(AttributeMappingRepository::class);
        $channelRepo = Mockery::mock(ChannelRepository::class);
        $credentialRepo = Mockery::mock(CredentialRepository::class);
        $productSource = Mockery::mock(ProductSource::class);

        $this->exporter = new Exporter(
            $batchRepo,
            $fileBuffer,
            $bagistoMapping,
            $attrRepo,
            $prodRepo,
            $catRepo,
            $attrOptionRepo,
            $attrMappingRepo,
            $channelRepo,
            $credentialRepo,
            $productSource
        );

        $this->jobLogger = Mockery::mock(LoggerInterface::class);
        $this->exporter->setLogger($this->jobLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_exporter_instantiation()
    {
        $this->assertInstanceOf(Exporter::class, $this->exporter);
    }

    public function test_keep_own_identity_fields_drops_inherited_sku_and_url_key()
    {
        $resolved = [
            'common' => [
                'sku'      => 'parent-sku',
                'url_key'  => 'parent-url-key',
                'brand'    => 'acme',
                'material' => 'cotton',
            ],
        ];

        $own = [
            'common' => [
                'color' => 'red',
            ],
        ];

        $method = new \ReflectionMethod($this->exporter, 'keepOwnIdentityFields');
        $method->setAccessible(true);
        $result = $method->invoke($this->exporter, $resolved, $own);

        $this->assertArrayNotHasKey('sku', $result['common']);
        $this->assertArrayNotHasKey('url_key', $result['common']);
        $this->assertSame('acme', $result['common']['brand']);
        $this->assertSame('cotton', $result['common']['material']);
    }

    public function test_keep_own_identity_fields_preserves_the_variants_own_identity()
    {
        $resolved = [
            'common' => [
                'sku'     => 'parent-sku',
                'url_key' => 'parent-url-key',
                'brand'   => 'acme',
            ],
        ];

        $own = [
            'common' => [
                'sku'     => 'variant-sku',
                'url_key' => 'variant-url-key',
            ],
        ];

        $method = new \ReflectionMethod($this->exporter, 'keepOwnIdentityFields');
        $method->setAccessible(true);
        $result = $method->invoke($this->exporter, $resolved, $own);

        $this->assertSame('variant-sku', $result['common']['sku']);
        $this->assertSame('variant-url-key', $result['common']['url_key']);
        $this->assertSame('acme', $result['common']['brand']);
    }

    public function test_keep_own_identity_fields_guards_nested_scopes()
    {
        $resolved = [
            'channel_locale_specific' => [
                'default' => [
                    'en_US' => [
                        'url_key' => 'parent-url-key',
                        'name'    => 'Parent Name',
                    ],
                ],
            ],
        ];

        $own = [
            'channel_locale_specific' => [
                'default' => [
                    'en_US' => [
                        'name' => 'Variant Name',
                    ],
                ],
            ],
        ];

        $method = new \ReflectionMethod($this->exporter, 'keepOwnIdentityFields');
        $method->setAccessible(true);
        $result = $method->invoke($this->exporter, $resolved, $own);

        $scope = $result['channel_locale_specific']['default']['en_US'];

        $this->assertArrayNotHasKey('url_key', $scope);
        $this->assertSame('Parent Name', $scope['name']);
    }

    public function test_apply_fixed_values_defaults_visible_individually_for_variant()
    {
        $this->setProperty($this->exporter, 'mappingAttributes', [
            'standard_attribute' => (object) ['fixed_value' => ['status' => '1']],
        ]);

        $mergedFields = [];
        $method = new \ReflectionMethod($this->exporter, 'applyFixedValues');
        $method->setAccessible(true);
        $method->invokeArgs($this->exporter, [&$mergedFields, ['sku' => 'parent-sku']]);

        $this->assertSame('0', $mergedFields['visible_individually']);
    }

    public function test_apply_fixed_values_keeps_mapped_visible_individually()
    {
        $this->setProperty($this->exporter, 'mappingAttributes', [
            'standard_attribute' => (object) ['fixed_value' => ['status' => '1']],
        ]);

        $mergedFields = ['visible_individually' => '0'];
        $method = new \ReflectionMethod($this->exporter, 'applyFixedValues');
        $method->setAccessible(true);
        $method->invokeArgs($this->exporter, [&$mergedFields, null]);

        $this->assertSame('0', $mergedFields['visible_individually']);
    }

    public function test_apply_fixed_values_sets_visible_individually_for_simple_product()
    {
        $this->setProperty($this->exporter, 'mappingAttributes', [
            'standard_attribute' => (object) ['fixed_value' => ['status' => '1']],
        ]);

        $mergedFields = [];
        $method = new \ReflectionMethod($this->exporter, 'applyFixedValues');
        $method->setAccessible(true);
        $method->invokeArgs($this->exporter, [&$mergedFields, null]);

        $this->assertSame('1', $mergedFields['visible_individually']);
    }

    public function test_super_attributes_flattens_a_one_level_variant_tree()
    {
        $this->jobLogger->shouldReceive('warning')->never();

        $item = $this->configurable(['color'], [
            $this->leaf('shirt-red', ['color' => 'red']),
            $this->leaf('shirt-blue', ['color' => 'blue']),
        ]);

        $this->assertSame(
            'sku=shirt-red,color=red|sku=shirt-blue,color=blue',
            $this->exporter->getSuperAttributes($item)
        );
    }

    public function test_super_attributes_flattens_a_two_level_variant_tree()
    {
        $this->jobLogger->shouldReceive('warning')->never();

        $item = $this->configurable(['color', 'size'], [
            $this->group('shirt-red', ['color' => 'red'], [
                $this->leaf('shirt-red-s', ['size' => 's']),
                $this->leaf('shirt-red-m', ['size' => 'm']),
            ]),
            $this->group('shirt-blue', ['color' => 'blue'], [
                $this->leaf('shirt-blue-m', ['size' => 'm']),
            ]),
        ]);

        $this->assertSame(
            'sku=shirt-red-s,color=red,size=s|sku=shirt-red-m,color=red,size=m|sku=shirt-blue-m,color=blue,size=m',
            $this->exporter->getSuperAttributes($item)
        );
    }

    public function test_super_attributes_lets_a_leaf_override_an_inherited_axis()
    {
        $this->jobLogger->shouldReceive('warning')->never();

        $item = $this->configurable(['color', 'size'], [
            $this->group('shirt-red', ['color' => 'red'], [
                $this->leaf('shirt-red-s', ['size' => 's']),
                $this->leaf('shirt-odd-m', ['color' => 'green', 'size' => 'm']),
            ]),
        ]);

        $this->assertSame(
            'sku=shirt-red-s,color=red,size=s|sku=shirt-odd-m,color=green,size=m',
            $this->exporter->getSuperAttributes($item)
        );
    }

    public function test_super_attributes_skips_a_leaf_missing_an_axis_and_says_why()
    {
        $this->jobLogger->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/Variant shirt-red-none not exported.*size/'));

        $item = $this->configurable(['color', 'size'], [
            $this->group('shirt-red', ['color' => 'red'], [
                $this->leaf('shirt-red-s', ['size' => 's']),
                $this->leaf('shirt-red-none', []),
            ]),
        ]);

        $this->assertSame('sku=shirt-red-s,color=red,size=s', $this->exporter->getSuperAttributes($item));
    }

    public function test_super_attributes_reports_a_variant_group_with_no_variants()
    {
        $this->jobLogger->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/Variant group shirt-empty has no variants of its own/'));

        $this->jobLogger->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/exported without variants/'));

        $item = $this->configurable(['color', 'size'], [
            $this->group('shirt-empty', ['color' => 'red'], []),
        ]);

        $this->assertSame('', $this->exporter->getSuperAttributes($item));
    }

    public function test_super_attributes_stops_below_the_supported_depth()
    {
        $this->jobLogger->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/deeper than 2 levels/'));

        $this->jobLogger->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/exported without variants/'));

        $item = $this->configurable(['color', 'size'], [
            $this->group('lvl1', ['color' => 'red'], [
                $this->group('lvl2', ['size' => 's'], [
                    $this->leaf('lvl3', ['size' => 'm']),
                ]),
            ]),
        ]);

        $this->assertSame('', $this->exporter->getSuperAttributes($item));
    }

    public function test_super_attributes_reports_a_configurable_with_no_axes()
    {
        $this->jobLogger->shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/has no super attributes/'));

        $item = $this->configurable([], [$this->leaf('shirt-red', ['color' => 'red'])]);

        $this->assertSame('', $this->exporter->getSuperAttributes($item));
    }

    public function test_associations_keep_only_skus_that_still_exist()
    {
        $this->expectSkuLookup(['shirt-red', 'ghost-sku'], ['shirt-red']);

        $this->assertSame('shirt-red', $this->associationsFormat('shirt-red, ghost-sku'));
    }

    public function test_associations_are_looked_up_once_per_sku()
    {
        $this->expectSkuLookup(['shirt-red', 'shirt-blue'], ['shirt-red', 'shirt-blue']);

        $this->assertSame('shirt-red,shirt-blue', $this->associationsFormat('shirt-red,shirt-blue'));
        $this->assertSame('shirt-blue', $this->associationsFormat('shirt-blue'));
    }

    public function test_associations_are_null_when_the_product_has_none()
    {
        $this->productRepository->shouldReceive('whereIn')->never();

        $this->assertNull($this->associationsFormat(null));
    }

    private function expectSkuLookup(array $queried, array $found): void
    {
        $this->productRepository->shouldReceive('whereIn')
            ->once()
            ->with('sku', $queried)
            ->andReturn(Mockery::mock(['pluck' => collect($found)]));
    }

    private function associationsFormat(?string $upSells): ?string
    {
        $item = $upSells === null
            ? ['values' => []]
            : ['values' => ['associations' => ['up_sells' => explode(',', $upSells)]]];

        $method = new \ReflectionMethod($this->exporter, 'getAssociationsFormat');
        $method->setAccessible(true);

        return $method->invoke($this->exporter, $item, 'up_sells');
    }

    private function configurable(array $axisCodes, array $variants): array
    {
        return [
            'sku'              => 'shirt',
            'type'             => 'configurable',
            'super_attributes' => array_map(fn ($code) => ['code' => $code], $axisCodes),
            'variants'         => $variants,
        ];
    }

    private function group(string $sku, array $axes, array $variants): array
    {
        return [
            'sku'      => $sku,
            'type'     => 'variant_group',
            'values'   => ['common' => $axes],
            'variants' => $variants,
        ];
    }

    private function leaf(string $sku, array $axes): array
    {
        return [
            'sku'      => $sku,
            'type'     => 'simple',
            'values'   => ['common' => $axes],
            'variants' => [],
        ];
    }

    private function setProperty(object $object, string $property, $value): void
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setAccessible(true);
        $ref->setValue($object, $value);
    }
}
