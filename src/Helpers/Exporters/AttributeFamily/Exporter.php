<?php

namespace Webkul\Bagisto\Helpers\Exporters\AttributeFamily;

use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Enums\Services\MethodType;
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

    public const BATCH_SIZE = 1;

    public const ENTITY_TYPE = 'attribute_family';

    public const GET_ENTITY_TYPE = 'getAttributeFamily';

    protected bool $exportsFile = false;

    protected $credential = [];

    protected $standardAttributes = [];

    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected BagistoDataMapping $bagistoDataMappingRepository,
        protected AttributeOptionRepository $attributeOptionsRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected CredentialRepository $credentialRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    public function initialize(): void
    {
        $this->initializeCredential($this->getFilters());

        $this->standardAttributes = array_column(config('bagisto-attributes'), 'code');
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

        $preparedData = $this->prepareAttributeFamilies($batch, $filePath);

        $this->write($preparedData, $batch->id);

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        return true;
    }

    /**
     * Get results based on filters.
     */
    protected function getResults(): \Iterator
    {
        $this->initialize();

        if (empty($this->credential)) {
            return new \ArrayIterator([false]);
        }

        $filters = $this->getFilters();
        $attributeFamilyCodes = $filters['code'] ?? null;

        return $attributeFamilyCodes
            ? $this->source->with(['familyGroups', 'attributeFamilyGroupMappings.customAttributes'])
                ->whereIn('code', $this->parseIdentifiers($attributeFamilyCodes))
                ->get()->getIterator()
            : $this->source->with(['familyGroups', 'attributeFamilyGroupMappings.customAttributes'])
                ->all()->getIterator();
    }

    /**
     * Writes the export data.
     */
    public function write($items, $batchId): void
    {
        foreach ($items as $item) {
            $id = $item['id'];
            unset($item['id']);

            $mapData = $this->getMapping($this->credential['id'], $id);
            $method = $mapData ? MethodType::PUT->value : MethodType::POST->value;
            $option = $mapData ? ['id' => $mapData->external_id] : [];

            $response = null;

            try {
                $response = $this->setApiRequest($method, self::ENTITY_TYPE, $item, $option);
            } catch (\Exception $e) {
                $this->skippedItemsCount++;
                $this->jobLogger->warning($item['code'].' export failed: '.$e->getMessage());

                continue;
            }

            if (isset($response['id'])) {
                $mapData ? $this->updatedItemsCount++ : $this->createdItemsCount++;
            } else {
                $this->handleMissingMapping($item, $response, $mapData, $batchId, $id);
                if (! empty($response)) {
                    $this->updatedItemsCount++;
                } else {
                    $this->skippedItemsCount++;
                    $this->jobLogger->warning(
                        $item['code'].' '.trans('bagisto::app.bagisto.export.mapping.attributes.skipped')
                        .' '.json_encode($response)
                        .' '.trans('bagisto::app.bagisto.export.mapping.attributes.data')
                        .' '.json_encode($item)
                    );
                }
            }

            $this->handleResponse($item, $response, $batchId, $mapData, $id);
        }
    }

    /**
     * Prepare attribute Families from current batch
     */
    public function prepareAttributeFamilies(JobTrackBatchContract $batch, mixed $filePath): array
    {
        $attributeFamilies = [];
        foreach ($batch->data as $rowData) {
            $attributeFamilies[] = $this->getCommonFields($rowData);
        }

        return $attributeFamilies;
    }

    /**
     * Handles API response and sets mapping.
     */
    protected function handleResponse($item, $response, $batchId, $mapData, $id): void
    {
        $this->mapResponseGroups($item, $response, $batchId);

        if (! $mapData && isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId, $item['code']);
        }
    }

    /**
     * Maps response groups if not already mapped.
     */
    private function mapResponseGroups($item, $response, $batchId): void
    {
        if (empty($response['groups'])) {
            return;
        }
        $groupIds = ! empty($item['attribute_groups']) ? array_keys($item['attribute_groups']) : [];

        foreach ($response['groups'] as $key => $groups) {
            if (! $this->getMapping($this->credential['id'], null, $groups['id'], $item['code'].'|'.$groups['code'], null, self::GROUP_ENTITY_TYPE) && isset($groupIds[$key])) {
                $this->setMapping(
                    $this->credential['id'],
                    str_replace('group_', '', $groupIds[$key]),
                    $groups['id'],
                    $batchId,
                    $item['code'].'|'.$groups['code'],
                    self::GROUP_ENTITY_TYPE
                );
            }
        }
    }

    /**
     * Handles missing mappings by retrieving Bagisto families.
     */
    private function handleMissingMapping($item, &$response, &$mapData, $batchId, $id): void
    {
        $bagistoFamily = $this->setApiRequest(MethodType::GET->value, self::GET_ENTITY_TYPE, [], ['id' => $item['code']]);

        if (empty($bagistoFamily)) {
            return;
        }

        if ($bagistoFamily['code'] === $item['code']) {
            $this->setMapping($this->credential['id'], $id, $bagistoFamily['id'], $batchId, $bagistoFamily['code']);
            $this->mapBagistoGroups($bagistoFamily, $item, $batchId);
            $response = $this->afterMappingResendRequest($bagistoFamily, $item, $batchId, $id);
            if (! empty($response['id'])) {
                $mapData = $response['id'];
                $this->handleResponse($item, $response, $batchId, $mapData, $id);
            }
        }
    }

    public function convertToPayload($attributeGroups): array
    {
        $transformedArray = [];

        foreach ($attributeGroups as $group) {
            $id = $group['id'];
            unset($group['id']);
            $transformedArray[$id] = $group;
        }

        return $transformedArray;
    }

    protected function afterMappingResendRequest($response, $item, $batchId, $id): mixed
    {
        return $this->setApiRequest(MethodType::PUT->value, self::ENTITY_TYPE, $item, ['id' => $response['id']]);
    }

    /**
     * Maps Bagisto groups with UnoPIM groups.
     */
    private function mapBagistoGroups($family, &$item, $batchId): void
    {
        $bagistoGroups = array_combine(
            array_column($family['attribute_groups'], 'id'),
            array_column($family['attribute_groups'], 'code')
        );
        $attributeGroups = ! empty($item['attribute_groups']) ? array_keys($item['attribute_groups']) : [];

        $unopimGroups = array_combine(
            array_map(fn ($group) => (int) preg_replace('/[^0-9]/', '', $group), $attributeGroups),
            array_column($item['attribute_groups'], 'code')
        );

        foreach ($bagistoGroups as $id => $groupCode) {
            if (! $this->getMapping($this->credential['id'], null, $id, $item['code'].'|'.$groupCode, null, self::GROUP_ENTITY_TYPE)
                && in_array($groupCode, $unopimGroups)) {
                $this->setMapping($this->credential['id'], array_search($groupCode, $unopimGroups), $id, $batchId, $item['code'].'|'.$groupCode, self::GROUP_ENTITY_TYPE);
                $groupKey = ! empty(array_search($groupCode, $unopimGroups)) ? 'group_'.array_search($groupCode, $unopimGroups) : 'group_0';
                if (! empty($item['attribute_groups'][$groupKey])) {
                    $item['attribute_groups'][$id] = $item['attribute_groups'][$groupKey];
                    unset($item['attribute_groups'][$groupKey]);
                }
            }
        }
    }

    /**
     * Formats common fields for export.
     */
    protected function getCommonFields($item): array
    {
        $formatData = [
            'id'               => $item['id'],
            'code'             => $item['code'],
            'name'             => ! empty($item['name']) ? $item['name'] : $item['code'],
            'attribute_groups' => $this->formatAttributeGroups($item),
        ];

        return $formatData;
    }

    /**
     * Formats attribute groups.
     */
    private function formatAttributeGroups($item): array
    {
        $formattedAttributes = [];

        foreach ($item['family_groups'] as $familyGroup) {
            foreach ($item['attribute_family_group_mappings'] as $groupMapping) {
                if ($groupMapping['attribute_group_id'] === $familyGroup['id']) {
                    $formattedAttributes[$this->getFormattedGroupKey($familyGroup)] = [
                        'position'          => $groupMapping['position'],
                        'column'            => 1,
                        'name'              => $familyGroup['name'],
                        'code'              => $familyGroup['code'],
                        'custom_attributes' => $this->formatCustomAttributes($groupMapping),
                    ];
                }
            }
        }

        return $formattedAttributes;
    }

    /**
     * Formats custom attributes.
     */
    private function formatCustomAttributes($groupMapping): array
    {
        $attributeIds = [];

        foreach ($groupMapping['custom_attributes'] as $key => $attribute) {
            $mapData = $this->getMapping($this->credential['id'], $attribute['id'], null, null, null, 'attribute');
            if ($mapData && ! in_array($attribute['code'], $this->standardAttributes)) {
                $attributeIds[$key] = [
                    'id'       => $mapData->external_id ?? $attribute['id'],
                    'code'     => $attribute['code'],
                    'position' => $attribute['pivot']['position'],
                ];
            }
        }

        return $attributeIds;
    }

    /**
     * Gets the formatted group key.
     */
    private function getFormattedGroupKey($familyGroup): string
    {
        $mapData = $this->getMapping($this->credential['id'], $familyGroup['id'], null, null, null, self::GROUP_ENTITY_TYPE);

        return $mapData ? $mapData->external_id : 'group_'.$familyGroup['id'];
    }
}
