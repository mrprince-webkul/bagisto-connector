<?php

use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Enums\Export\ProductFilter as BagistoProductFilter;
use Webkul\Bagisto\Enums\Export\ProductStatus;
use Webkul\Bagisto\Helpers\Exporters\Attribute\Exporter as AttributeExporter;
use Webkul\Bagisto\Helpers\Exporters\AttributeFamily\Exporter as AttributeFamilyExporter;
use Webkul\Bagisto\Helpers\Exporters\Category\Exporter as CategoryExporter;
use Webkul\Bagisto\Helpers\Exporters\Product\Exporter as ProductExporter;
use Webkul\Bagisto\Validators\JobInstances\Export\ProductJobValidator;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\DataTransfer\Enums\CompletenessCondition;
use Webkul\DataTransfer\Enums\ProductFilter;
use Webkul\DataTransfer\Enums\TimeCondition;
use Webkul\Product\Repositories\ProductRepository;

$credentialsField = [
    'name'       => BagistoProductFilter::CREDENTIALS->value,
    'title'      => 'bagisto::app.exporters.bagisto.credentials',
    'required'   => true,
    'validation' => 'required',
    'type'       => 'select',
    'async'      => true,
    'track_by'   => 'id',
    'label_by'   => 'shop_url',
    'list_route' => 'bagisto.credential.fetch-all',
];

$channelField = [
    'name'       => BagistoProductFilter::CHANNEL->value,
    'title'      => 'bagisto::app.exporters.bagisto.channel',
    'required'   => true,
    'validation' => 'required',
    'type'       => 'select',
    'async'      => true,
    'track_by'   => 'code',
    'label_by'   => 'name',
    'list_route' => 'bagisto.channel.fetch-all',
];

$localeField = [
    'name'       => BagistoProductFilter::LOCALE->value,
    'title'      => 'bagisto::app.exporters.bagisto.locale',
    'required'   => true,
    'validation' => 'required',
    'type'       => 'multiselect',
    'async'      => true,
    'track_by'   => 'code',
    'label_by'   => 'name',
    'list_route' => 'bagisto.locale.fetch-all',
];

$codeField = [
    'name'     => BagistoProductFilter::CODE->value,
    'title'    => 'bagisto::app.exporters.bagisto.code',
    'required' => false,
    'type'     => 'tags',
];

/**
 * Category, attribute and attribute family exports all take the same scope plus
 * an optional code list, so they share one field set.
 */
$scopedCodeFilters = [
    'fields' => [$credentialsField, $channelField, $localeField, $codeField],
];

$visibleWhenTimeConditionIs = fn (TimeCondition $condition): array => [
    'field'  => ProductFilter::TIME_CONDITION->value,
    'values' => [$condition->value],
];

return [
    'bagisto_categories' => [
        'title'    => 'bagisto::app.exporters.bagisto.category',
        'exporter' => CategoryExporter::class,
        'source'   => CategoryRepository::class,
        'filters'  => $scopedCodeFilters,
    ],

    'bagisto_attribute' => [
        'title'    => 'bagisto::app.exporters.bagisto.attribute',
        'exporter' => AttributeExporter::class,
        'source'   => AttributeRepository::class,
        'filters'  => $scopedCodeFilters,
    ],

    'bagisto_attribute_families' => [
        'title'    => 'bagisto::app.exporters.bagisto.attribute-families',
        'exporter' => AttributeFamilyExporter::class,
        'source'   => AttributeFamilyRepository::class,
        'filters'  => $scopedCodeFilters,
    ],

    'bagisto_product' => [
        'title'     => 'bagisto::app.exporters.bagisto.product',
        'exporter'  => ProductExporter::class,
        'source'    => ProductRepository::class,
        'validator' => ProductJobValidator::class,
        'filters'   => [
            'fields' => [
                $credentialsField,
                [...$channelField, 'type' => 'multiselect'],
                $localeField,
                [
                    'name'       => ProductFilter::ATTRIBUTE_FAMILIES->value,
                    'title'      => 'data_transfer::app.exporters.products.filters.attribute-families',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'list_route' => 'admin.settings.data_transfer.exports.filters.attribute_families',
                ], [
                    'name'       => BagistoProductFilter::TYPE->value,
                    'title'      => 'bagisto::app.exporters.bagisto.type',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'bagisto.type.fetch-all',
                ], [
                    'name'     => ProductFilter::STATUS->value,
                    'title'    => 'bagisto::app.exporters.bagisto.status',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'value' => ProductStatus::ALL->value,
                            'label' => 'bagisto::app.exporters.bagisto.all',
                        ], [
                            'value' => ProductStatus::ENABLED->value,
                            'label' => 'bagisto::app.exporters.bagisto.true',
                        ], [
                            'value' => ProductStatus::DISABLED->value,
                            'label' => 'bagisto::app.exporters.bagisto.false',
                        ],
                    ],
                ], [
                    'name'     => ProductFilter::COMPLETENESS->value,
                    'title'    => 'data_transfer::app.exporters.products.filters.completeness',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'value' => CompletenessCondition::NONE->value,
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.none',
                        ], [
                            'value' => CompletenessCondition::AT_LEAST_ONE->value,
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.at-least-one',
                        ], [
                            'value' => CompletenessCondition::ALL->value,
                            'label' => 'data_transfer::app.exporters.products.filters.completeness-options.all',
                        ],
                    ],
                ], [
                    'name'     => ProductFilter::TIME_CONDITION->value,
                    'title'    => 'data_transfer::app.exporters.products.filters.time-condition',
                    'required' => false,
                    'type'     => 'select',
                    'options'  => [
                        [
                            'value' => TimeCondition::NONE->value,
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.none',
                        ], [
                            'value' => TimeCondition::LAST_N_DAYS->value,
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.last-n-days',
                        ], [
                            'value' => TimeCondition::SINCE_LAST_EXPORT->value,
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.since-last-export',
                        ], [
                            'value' => TimeCondition::BETWEEN_DATES->value,
                            'label' => 'data_transfer::app.exporters.products.filters.time-options.between-dates',
                        ],
                    ],
                ], [
                    'name'         => ProductFilter::TIME_VALUE->value,
                    'title'        => 'data_transfer::app.exporters.products.filters.time-value',
                    'required'     => false,
                    'type'         => 'number',
                    'visible_when' => $visibleWhenTimeConditionIs(TimeCondition::LAST_N_DAYS),
                ], [
                    'name'         => ProductFilter::TIME_DATE->value,
                    'title'        => 'data_transfer::app.exporters.products.filters.time-date',
                    'required'     => false,
                    'type'         => 'date',
                    'visible_when' => $visibleWhenTimeConditionIs(TimeCondition::BETWEEN_DATES),
                ], [
                    'name'         => ProductFilter::TIME_DATE_END->value,
                    'title'        => 'data_transfer::app.exporters.products.filters.time-date-end',
                    'required'     => false,
                    'type'         => 'date',
                    'visible_when' => $visibleWhenTimeConditionIs(TimeCondition::BETWEEN_DATES),
                ], [
                    'name'       => ProductFilter::CATEGORIES->value,
                    'title'      => 'data_transfer::app.exporters.products.filters.categories',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'full_width' => true,
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'label',
                    'list_route' => 'admin.settings.data_transfer.exports.filters.categories',
                ], [
                    'name'     => BagistoProductFilter::WITH_MEDIA->value,
                    'title'    => 'bagisto::app.exporters.bagisto.with_media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => BagistoProductFilter::WITH_ASSOCIATIONS->value,
                    'title'    => 'data_transfer::app.exporters.fields.with-associations',
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => '1',
                ], [
                    'name'       => ProductFilter::SKU->value,
                    'title'      => 'bagisto::app.exporters.bagisto.sku',
                    'info'       => 'data_transfer::app.exporters.products.filters.identifiers-info',
                    'required'   => false,
                    'type'       => 'tags',
                    'full_width' => true,
                ], [
                    'name'         => ProductFilter::CUSTOM_ATTRIBUTES->value,
                    'required'     => false,
                    'type'         => 'attribute-conditions',
                    'full_width'   => true,
                    'async'        => true,
                    'list_route'   => 'admin.settings.data_transfer.exports.filters.attributes',
                    'query_params' => ['exclude' => [ProductFilter::SKU->value]],
                ],
            ],
        ],
    ],
];
