<?php

namespace Webkul\Bagisto\Helpers\Exporters\Attribute;

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Enums\Services\MethodType;
use Webkul\Bagisto\Repositories\AttributeMappingRepository;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Bagisto\Traits\ApiRequest as ApiRequestTrait;
use Webkul\Bagisto\Traits\Credential as CredentialTrait;
use Webkul\Bagisto\Traits\ExportSummary as ExportSummaryTrait;
use Webkul\Bagisto\Traits\Mapping as MappingTrait;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    use ApiRequestTrait;
    use CredentialTrait;
    use ExportSummaryTrait;
    use MappingTrait;

    public const BATCH_SIZE = 10;

    public const ENTITY_TYPE = 'attribute';

    public const GET_ENTITY_TYPE = 'getAttribute';

    protected bool $exportsFile = false;

    protected array $credential = [];

    protected array $additionalInfoValue = [];

    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected BagistoDataMapping $bagistoDataMappingRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeMappingRepository $attributeMappingRepository,
        protected CredentialRepository $credentialRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    public function initialize(): void
    {
        $this->initializeCredential($this->getFilters());

        $this->additionalInfoValue = Cache::get(CacheType::ADDITIONAL_INFO->value, []);
    }

    public function checkRequiredCondition(): bool
    {
        if (empty($this->credential)) {
            $this->jobLogger->warning('Credential not found!');

            return true;
        }

        return false;
    }

    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        $this->initialize();

        if ($this->checkRequiredCondition()) {
            return false;
        }

        $preparedData = $this->prepareAttributes($batch, $filePath);

        $this->write($preparedData, $batch->id);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults(): \Iterator
    {
        $this->initialize();

        if (empty($this->credential)) {
            return new \ArrayIterator([false]);
        }

        $filters = $this->getFilters();
        $attributeCodes = $filters['code'] ?? null;
        $mappedAttributeValue = [];
        $mappedAttributeValue = $this->attributeMappingRepository->findByField('section', 'standard_attribute')->first();
        $mappedAttributes = [];
        if ($mappedAttributeValue) {
            $additionalInfo = $mappedAttributeValue?->additional_info ?? [];
            ! empty($additionalInfo['configurable_attribute']) ? $additionalInfoValue = explode(',', $additionalInfo['configurable_attribute']) : $additionalInfoValue = [];
            $mappedAttributes = array_unique(array_values($mappedAttributeValue?->mapped_value));
            $mappedAttributes = array_unique(array_merge($mappedAttributes, $additionalInfoValue));
            Cache::put(CacheType::ADDITIONAL_INFO->value, $additionalInfoValue, config('session.lifetime'));
        }

        if ($attributeCodes) {
            return $this->source->with('options')
                ->whereIn('code', $this->parseIdentifiers($attributeCodes))
                ->get()->getIterator();
        }

        if (! empty($mappedAttributes)) {
            return $this->source->with('options')
                ->whereIn('code', $mappedAttributes)
                ->get()->getIterator();
        }

        $this->jobLogger?->warning(
            'No attribute mapping configured and no code filter set: exporting every attribute.'
        );

        return $this->source->with('options')->all()->getIterator();
    }

    public function write($items, $batchId): void
    {
        foreach ($items as $item) {
            $id = $item['id'];
            unset($item['id']);
            $mapData = $this->getMapping($this->credential['id'], $id);
            $method = $mapData ? MethodType::PUT->value : MethodType::POST->value;
            $option = $mapData ? ['id' => $mapData->external_id] : [];

            if (in_array($item['code'], $this->additionalInfoValue)) {
                $item['is_configurable'] = 1;
                $item['is_visible_on_front'] = 1;
            }

            try {
                $response = $this->setApiRequest($method, self::ENTITY_TYPE, $item, $option);
            } catch (\Exception $e) {
                $this->jobLogger->warning($e);

                continue;
            }

            if (isset($response['id'])) {
                $mapData ? $this->updatedItemsCount++ : $this->createdItemsCount++;
            } else {
                $this->createMappingAlreadyExistAttributes($response, $item, $mapData, $batchId, $id);
                if (! empty($response)) {
                    $this->updatedItemsCount++;
                } else {
                    $this->skippedItemsCount++;
                }
            }

            $this->handleOptions($item, $response, $batchId, $mapData, $id);
        }
    }

    private function handleOptions(array $item, ?array $response, int $batchId, $mapData, int $id): void
    {
        if (! empty($response['options']) && in_array($response['type'], ['select', 'multiselect', 'checkbox'])) {
            $optionIds = ! empty($item['options']) ? array_keys($item['options']) : [];
            foreach ($response['options'] as $key => $option) {
                if (! $this->getMapping($this->credential['id'], null, $option['id'], null, null, 'option') && ! empty($optionIds[$key])) {
                    $this->setMapping($this->credential['id'], str_replace('option_', '', $optionIds[$key]), $option['id'], $batchId, $option['label'] ?? $option['admin_name'], 'option');
                }
            }
        }

        if (! $mapData && isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId, $item['code']);
        }
    }

    private function createMappingAlreadyExistAttributes(&$response, $item, &$mapData, $batchId, $id): void
    {
        if ($mapData || ! empty($response)) {
            return;
        }

        $apiResponse = $this->setApiRequest(MethodType::GET->value, self::GET_ENTITY_TYPE, [], ['id' => $item['code']]);

        if (empty($apiResponse)) {
            return;
        }

        if (in_array($apiResponse['type'], ['select', 'multiselect', 'checkbox'])) {
            $this->setMapping($this->credential['id'], $id, $apiResponse['id'], $batchId, $item['code']);
            foreach ($apiResponse['options'] ?? [] as $value) {
                $key = null;
                if (! empty($item['options'])) {
                    foreach ($item['options'] as $optionKey => $option) {
                        if ($option['admin_name'] === $value['admin_name']) {
                            $item['options'][$optionKey]['isNew'] = false;
                            $item['options'][$value['id']] = $item['options'][$optionKey];
                            unset($item['options'][$optionKey]);
                            $key = $optionKey;
                            break;
                        }
                    }
                }

                $mappedKey = $key ? str_replace('option_', '', $key) : null;
                $this->setMapping($this->credential['id'], $mappedKey, $value['id'], $batchId, $value['label'] ?? $value['admin_name'], 'option');
            }
        } else {
            $this->setMapping($this->credential['id'], $id, $apiResponse['id'], $batchId, $item['code']);
        }

        $option = ['id' => $apiResponse['id']];
        $response = $this->setApiRequest(MethodType::PUT->value, self::ENTITY_TYPE, $item, $option);
        $mapData = $response;
    }

    /**
     * Prepare attributes from current batch
     */
    public function prepareAttributes(JobTrackBatchContract $batch, mixed $filePath): array
    {
        $attributes = [];
        $filters = $this->getFilters();
        $bagistoLocales = $this->getMappedLocales();
        $bagistoChannel = $this->findMappedChannel($filters['channel']);

        if (! $bagistoChannel || empty($bagistoLocales[$bagistoChannel])) {
            $this->skippedItemsCount += count($batch->data);

            $this->jobLogger?->warning(
                count($batch->data).' attributes not exported: no Bagisto channel/locale mapping for "'
                .$filters['channel'].'". Open the credential and save the channel and locale mapping.'
            );

            return $attributes;
        }

        foreach ($batch->data as $rowData) {
            $attributes[] = $this->getCommonFields($rowData, $bagistoLocales[$bagistoChannel]);
        }

        return $attributes;
    }

    protected function getCommonFields($item, $locale): array
    {
        $locale = array_flip($locale);
        unset($item['created_at'], $item['updated_at']);
        $item = array_filter($item);

        if ($item['type'] === 'gallery') {
            $item['type'] = 'image';
        }

        if (isset($item['translations'])) {
            foreach ($item['translations'] as $translation) {
                if (isset($locale[$translation['locale']])) {
                    $bagistoLocal = $locale[$translation['locale']];
                    $item[$bagistoLocal]['name'] = $translation['name'];
                }
            }
        }

        if (in_array($item['type'], ['select', 'multiselect', 'checkbox'])) {
            $this->processOptions($item, $locale);
            if ($item['type'] === 'select') {
                $item['is_configurable'] = true;
            }
        }

        if (! empty($item['validation']) && $item['validation'] == 'number') {
            $item['validation'] = 'numeric';
        }

        $item['is_required'] = $item['is_required'] ?? 0;
        $item['admin_name'] = $item['name'] ?? $item['code'];
        unset($item['translations']);

        return $item;
    }

    private function processOptions(array &$item, array $locale): void
    {
        if (! empty($item['options'])) {
            $data = [];
            foreach ($item['options'] as $key => $option) {
                $mapData = $this->getMapping($this->credential['id'], $option['id'], null, null, null, 'option');
                $optionKey = $mapData ? $mapData->external_id : 'option_'.$option['id'];
                $data[$optionKey] = [
                    'swatch_value' => $option['swatch_value'],
                    'isNew'        => ! $mapData,
                    'isDelete'     => false,
                    'admin_name'   => $option['code'],
                    'sort_order'   => $option['sort_order'],
                ];

                foreach ($option['translations'] as $translation) {
                    if (isset($locale[$translation['locale']])) {
                        $bagistoLocal = $locale[$translation['locale']];
                        $data[$optionKey][$bagistoLocal]['label'] = $translation['label'];
                    }
                }
            }

            $item['options'] = ! empty($data) ? $data : null;
        }
    }
}
