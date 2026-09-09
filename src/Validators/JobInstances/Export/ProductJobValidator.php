<?php

namespace Webkul\Bagisto\Validators\JobInstances\Export;

use BackedEnum;
use Webkul\Bagisto\Enums\Export\ProductFilter as BagistoProductFilter;
use Webkul\Bagisto\Enums\Export\ProductStatus;
use Webkul\DataTransfer\Enums\CompletenessCondition;
use Webkul\DataTransfer\Enums\ProductFilter;
use Webkul\DataTransfer\Enums\TimeCondition;
use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class ProductJobValidator extends JobValidator
{
    protected const BOOLEAN_RULE = 'nullable|in:1,0';

    public function getRules(array $options): array
    {
        return [
            $this->field(BagistoProductFilter::WITH_MEDIA)        => self::BOOLEAN_RULE,
            $this->field(BagistoProductFilter::WITH_ASSOCIATIONS) => self::BOOLEAN_RULE,
            $this->field(ProductFilter::SKU)                      => 'nullable|string',
            $this->field(ProductFilter::STATUS)                   => $this->oneOf(ProductStatus::values()),
            $this->field(ProductFilter::COMPLETENESS)             => $this->oneOf(CompletenessCondition::values()),
            $this->field(ProductFilter::TIME_CONDITION)           => $this->oneOf(TimeCondition::values()),
            $this->field(ProductFilter::TIME_VALUE)               => 'nullable|integer|min:1|'.$this->requiredForCondition(TimeCondition::LAST_N_DAYS),
            $this->field(ProductFilter::TIME_DATE)                => 'nullable|date|'.$this->requiredForCondition(TimeCondition::BETWEEN_DATES),
            $this->field(ProductFilter::TIME_DATE_END)            => 'nullable|date|after_or_equal:'.$this->field(ProductFilter::TIME_DATE).'|'.$this->requiredForCondition(TimeCondition::BETWEEN_DATES),
        ];
    }

    /**
     * Resolved at validation time so the labels follow the request locale.
     */
    public function getAttributeNames(array $options): array
    {
        return [
            $this->field(BagistoProductFilter::WITH_MEDIA)        => trans('bagisto::app.exporters.bagisto.with_media'),
            $this->field(BagistoProductFilter::WITH_ASSOCIATIONS) => trans('data_transfer::app.exporters.fields.with-associations'),
            $this->field(ProductFilter::SKU)                      => trans('bagisto::app.exporters.bagisto.sku'),
            $this->field(ProductFilter::STATUS)                   => trans('bagisto::app.exporters.bagisto.status'),
            $this->field(ProductFilter::ATTRIBUTE_FAMILIES)       => trans('data_transfer::app.exporters.products.filters.attribute-families'),
            $this->field(ProductFilter::CATEGORIES)               => trans('data_transfer::app.exporters.products.filters.categories'),
            $this->field(ProductFilter::COMPLETENESS)             => trans('data_transfer::app.exporters.products.filters.completeness'),
            $this->field(ProductFilter::TIME_CONDITION)           => trans('data_transfer::app.exporters.products.filters.time-condition'),
            $this->field(ProductFilter::TIME_VALUE)               => trans('data_transfer::app.exporters.products.filters.time-value'),
            $this->field(ProductFilter::TIME_DATE)                => trans('data_transfer::app.exporters.products.filters.time-date'),
            $this->field(ProductFilter::TIME_DATE_END)            => trans('data_transfer::app.exporters.products.filters.time-date-end'),
        ];
    }

    protected function field(BackedEnum $filter): string
    {
        return 'filters.'.$filter->value;
    }

    protected function oneOf(array $values): string
    {
        return 'nullable|in:'.implode(',', $values);
    }

    protected function requiredForCondition(TimeCondition $condition): string
    {
        return 'required_if:'.$this->field(ProductFilter::TIME_CONDITION).','.$condition->value;
    }
}
