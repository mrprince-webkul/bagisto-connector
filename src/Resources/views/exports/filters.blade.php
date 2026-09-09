@php
    $bagistoConnectorFields = \Webkul\Bagisto\Enums\Export\ProductFilter::connectorFields();
@endphp

<div
    v-if="filterFields.some(field => (field.list_route || '').includes('/bagisto/')) && filterFields.some(field => @js($bagistoConnectorFields).includes(field.name))"
    class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
>
    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
        @lang('bagisto::app.exporters.bagisto.filters')
    </p>

    <x-admin::data-transfer.filter-fields
        ::entity-type="entityType"
        :exporter-config="$exporterConfig ?? config('exporters')"
        :only="implode(',', $bagistoConnectorFields)"
        grid-class="grid grid-cols-1"
    />
</div>
