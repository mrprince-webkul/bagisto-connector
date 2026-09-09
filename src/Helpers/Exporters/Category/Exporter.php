<?php

namespace Webkul\Bagisto\Helpers\Exporters\Category;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Enums\Services\MethodType;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CategoryFieldMappingRepository;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Bagisto\Traits\ApiRequest as ApiRequestTrait;
use Webkul\Bagisto\Traits\Credential as CredentialTrait;
use Webkul\Bagisto\Traits\ExportSummary as ExportSummaryTrait;
use Webkul\Bagisto\Traits\Mapping as MappingTrait;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Category\Exporter as BaseExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends BaseExporter
{
    use ApiRequestTrait;
    use CredentialTrait;
    use ExportSummaryTrait;
    use MappingTrait;

    public const ENTITY_TYPE = 'category';

    protected bool $exportsFile = false;

    protected array $credential = [];

    protected array $mappingFields = [];

    protected array $jobFilters = [];

    /**
     * Active UnoPim category fields, as a Collection once loaded from the
     * repository and an array when restored from cache.
     *
     * @var Collection|array
     */
    protected $categoryFields = [];

    protected array $storeSlug = [];

    /**
     * Memoised fallback list of Bagisto filterable attribute IDs.
     */
    protected ?array $defaultFilterableAttributeIds = null;

    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected BagistoDataMapping $bagistoDataMappingRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryFieldMappingRepository $categoryFieldMappingRepository,
        protected CredentialRepository $credentialRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer, $categoryFieldRepository);
    }

    public function initialize(): void
    {
        $this->initializeCredential($this->getFilters());

        $this->initializeCategoryFields();

        $this->initializeMappingFields();

        $this->initializeJobFilters();
    }

    /**
     * Initializes categoryFields for the export process.
     */
    public function initializeCategoryFields(): void
    {
        $this->categoryFields = Cache::get(CacheType::UNOPIM_CATEGORY_FIELDS->value, []);
        if (empty($this->categoryFields)) {
            $this->categoryFields = $this->categoryFieldRepository->getActiveCategoryFields();

            Cache::put(CacheType::UNOPIM_CATEGORY_FIELDS->value, $this->categoryFields, config('session.lifetime'));
        }
    }

    /**
     * Initializes mappingField for the export process.
     */
    public function initializeMappingFields(): void
    {
        $this->mappingFields = Cache::get(CacheType::CATEGORY_FIELD_MAPPING->value, []);
        if (empty($this->mappingFields)) {
            $mapping = $this->categoryFieldMappingRepository->findByField('section', 'standard_field')->first();

            if (! $mapping) {
                $mapping = (object) [
                    'mapped_value' => [],
                    'fixed_value'  => [],
                ];
            }

            $this->mappingFields = [
                'standard_field' => $mapping,
            ];

            Cache::put(CacheType::CATEGORY_FIELD_MAPPING->value, $this->mappingFields, config('session.lifetime'));
        }
    }

    /**
     * Initializes Job Filters for the export process.
     */
    public function initializeJobFilters(): void
    {
        $this->jobFilters = Cache::get(CacheType::CATEGORY_JOB_FILTERS->value, []);
        if (empty($this->jobFilters)) {
            $filters = $this->getFilters();

            $filtersLocales = [];
            $exportBagistoChannel = [];
            $exportBagistoLocales = [];

            if (! empty($filters['locale'])) {
                $filtersLocales = explode(',', $filters['locale']);
            }

            $bagistoLocales = $this->getMappedLocales();
            $bagistoChannels = $this->getMappedChannels();

            if (empty($filters['channel'])) {
                foreach ($bagistoChannels as $bChannel => $uChannel) {
                    $exportBagistoChannel[$uChannel] = $bChannel;
                    $mappedLocales = $bagistoLocales[$bChannel] ?? [];
                    foreach ($mappedLocales as $bagistoLocaleCode => $unopimLocaleCode) {
                        if (empty($filtersLocales) || in_array($unopimLocaleCode, $filtersLocales)) {
                            $exportBagistoLocales[$bagistoLocaleCode] = $unopimLocaleCode;
                        }
                    }
                }
            } else {
                $bagistoChannel = array_search($filters['channel'], $bagistoChannels);
                if ($bagistoChannel !== false && $bagistoChannel !== null) {
                    $exportBagistoChannel[$filters['channel']] = $bagistoChannel;
                    $mappedLocales = $bagistoLocales[$bagistoChannel] ?? [];
                    foreach ($mappedLocales as $bagistoLocaleCode => $unopimLocaleCode) {
                        if (empty($filtersLocales) || in_array($unopimLocaleCode, $filtersLocales)) {
                            $exportBagistoLocales[$bagistoLocaleCode] = $unopimLocaleCode;
                        }
                    }
                }
            }

            $this->jobFilters = [
                'channel' => $exportBagistoChannel,
                'locales' => $exportBagistoLocales,
            ];

            Cache::put(CacheType::CATEGORY_JOB_FILTERS->value, $this->jobFilters, config('session.lifetime'));
        }
    }

    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        $this->initialize();

        $preparedData = $this->prepareCategories($batch, $filePath);

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
    protected function getResults(): ?\Iterator
    {
        $filters = $this->getFilters();
        if (! empty($filters['code'])) {

            return $this->source->whereIn('code', $this->parseIdentifiers($filters['code']))->orderBy('parent_id')->with('parent_category')->get()?->getIterator();
        }

        return $this->source->orderBy('parent_id')->with('parent_category')->all()?->getIterator();
    }

    public function write($items, $batchId): void
    {
        foreach ($items as $item) {
            $id = $item['id'];
            unset($item['id']);

            $options = $this->prepareOptions($item);
            $mapData = $this->getMapping($this->credential['id'], $id);

            if ($this->shouldSkipRootCategory($item, $id, $mapData, $batchId)) {
                continue;
            }

            $this->handleSlugMapping($item, $mapData);

            $parentCode = $item['parent_id'] ?? null;

            $this->setParentId($item);

            $externalId = $this->prepareExternalId($item, $mapData);
            $options = array_merge($options, $externalId);
            if (($item['logo_path'] ?? null) == null) {
                unset($item['logo_path']);
            }
            if (($item['banner_path'] ?? null) == null) {
                unset($item['banner_path']);
            }
            $this->processApiRequest($item, $options, $mapData, $id, $batchId, $parentCode);
        }
    }

    private function prepareOptions(array $item): array
    {
        $mediaOptions = [];
        $mediaCodes = [];

        if (! empty($item['banner_path'])) {
            $mediaCodes[] = 'banner_path';
        }

        if (! empty($item['logo_path'])) {
            $mediaCodes[] = 'logo_path';
        }

        if (! empty($mediaCodes)) {
            $mediaOptions['isMultipart'] = true;
            $mediaOptions['mediaCodes'] = $mediaCodes;
        }

        return $mediaOptions;
    }

    private function shouldSkipRootCategory(array $item, $id, $mapData, $batchId): bool
    {
        if ($item['code'] === 'root' && ! $mapData) {
            $this->setMapping($this->credential['id'], $id, 1, $batchId, $this->createSlug($item['name']));

            return true;
        }

        return false;
    }

    private function handleSlugMapping(array &$item, $mapData): void
    {
        if (empty($this->mappingFields['standard_field']->mapped_value['slug'])) {
            $slug = $this->createSlug($item['name']);
            $mapDataName = $this->getMapping($this->credential['id'], null, null, $slug, null, self::ENTITY_TYPE, 'get');

            if (! $mapDataName->isEmpty() && ! $mapData) {
                $item['slug'] = $slug.'-'.count($mapDataName);
            } elseif (! $mapDataName->isEmpty() && $mapData) {
                in_array($slug, $this->storeSlug) ? $item['slug'] = $slug.'-'.array_count_values($this->storeSlug)[$slug] : '';

                if ($slug !== null) {
                    $this->storeSlug[] = $slug;
                }
            }
        }
    }

    private function setParentId(array &$item): void
    {
        if ($parentId = $this->getParentId($item['parent_id'] ?? null)) {
            $item['parent_id'] = $parentId;
        } else {
            unset($item['parent_id']);
        }
    }

    private function prepareExternalId(array &$item, $mapData): array
    {
        if ($mapData) {
            $item['_method'] = 'put';
            $item = $this->prepareCategoriesUpdataData($item);

            return ['id' => $mapData->external_id];
        }

        $item['locale'] = 'all';

        return [];
    }

    private function processApiRequest(array $item, array $options, $mapData, $id, $batchId, $parentCode = null): void
    {
        $response = $this->setApiRequest(MethodType::POST->value, self::ENTITY_TYPE, $item, $options);

        if (! $mapData && isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId, $this->createSlug($item['name']));
        }

        if (isset($response['id'])) {
            $mapData ? $this->updatedItemsCount++ : $this->createdItemsCount++;

            return;
        }

        if ($mapData && $this->isMissingEntityError()) {
            $this->recreateMissingCategory($item, $options, $id, $batchId, $parentCode);

            return;
        }

        $this->logSkippedItem($item, $response);
    }

    /**
     * Recreate a category whose Bagisto mapping became stale, then refresh the mapping.
     */
    private function recreateMissingCategory(array $item, array $options, $id, $batchId, $parentCode): void
    {
        $locale = $item['locale'];
        unset($item[$locale], $item['_method'], $options['id']);
        $item['locale'] = 'all';

        if ($parentId = $this->getParentId($parentCode)) {
            $item['parent_id'] = $parentId;
        } else {
            unset($item['parent_id']);
        }

        $response = $this->setApiRequest(MethodType::POST->value, self::ENTITY_TYPE, $item, $options);

        if (isset($response['id'])) {
            $this->setMapping($this->credential['id'], $id, $response['id'], $batchId, $this->createSlug($item['name']));
            $this->createdItemsCount++;
        } else {
            $this->logSkippedItem($item, $response);
        }
    }

    /**
     * Whether the most recent API error indicates the target entity no longer exists in Bagisto (HTTP 404).
     */
    private function isMissingEntityError(): bool
    {
        return array_key_exists('endpoint', $this->lastApiErrors);
    }

    private function logSkippedItem(array $item, ?array $response): void
    {
        $this->skippedItemsCount++;
        $reason = ! empty($this->lastApiErrors) ? json_encode($this->lastApiErrors) : json_encode($response);
        $this->jobLogger->warning(
            "Category {$item['code']} ".trans('bagisto::app.bagisto.export.mapping.attributes.skipped').
            " Reason: {$reason} ".
            trans('bagisto::app.bagisto.export.mapping.attributes.data').' '.json_encode($item)
        );
    }

    /**
     * Prepare categories from current batch
     */
    public function prepareCategories(JobTrackBatchContract $batch, mixed $filePath): array
    {
        $locales = $this->getExportableLocales();

        if ($locales === []) {
            $this->skippedItemsCount += count($batch->data);

            $this->jobLogger?->warning(
                count($batch->data).' categories not exported: no usable locale mapping. '
                .'Open the credential and re-save the channel and locale mapping.'
            );

            return [];
        }

        $categories = [];

        foreach ($batch->data as $rowData) {
            foreach ($locales as $bagistoLocale => $unopimLocale) {
                $categories[] = $this->processCategoryRow($rowData, $bagistoLocale, $unopimLocale, $filePath);
            }
        }

        return $categories;
    }

    /**
     * A half-saved credential mapping can leave a locale entry holding an array
     * where a locale code belongs, which would fatal the whole batch. Drop those
     * and say why, so the run reports a skip instead of dying.
     */
    private function getExportableLocales(): array
    {
        $locales = [];

        foreach ((array) ($this->jobFilters['locales'] ?? []) as $bagistoLocale => $unopimLocale) {
            if (is_string($bagistoLocale) && is_string($unopimLocale)) {
                $locales[$bagistoLocale] = $unopimLocale;

                continue;
            }

            $this->jobLogger?->warning(
                'Locale mapping entry ignored: expected a UnoPim locale code for Bagisto locale "'
                .(is_string($bagistoLocale) ? $bagistoLocale : gettype($bagistoLocale)).'", got '
                .gettype($unopimLocale).' ('.json_encode($unopimLocale).').'
            );
        }

        return $locales;
    }

    private function processCategoryRow(array $rowData, string $bagistoLocale, string $unopimLocale, mixed $filePath): array
    {
        $commonFields = $this->getCommonFields($rowData);
        $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $unopimLocale);
        $mergedFields = array_merge($commonFields, $localeSpecificFields);

        $additionalData = $this->setFieldsAdditionalData($mergedFields, $filePath);
        $attributes = $this->prepareCategoryAttributes($additionalData);

        $data = array_merge([
            'id'       => $rowData['id'],
            'code'     => $rowData['code'],
            'name'     => $rowData['name'] ?? $rowData['code'],
            'locale'   => $bagistoLocale,
            'position' => 1,
        ], $additionalData, $attributes);

        $data['position'] = isset($data['position']) && is_numeric($data['position'])
            ? (int) $data['position']
            : 1;

        if (empty($data['description']) && ($data['display_mode'] ?? null) === 'products_and_description') {
            $data['display_mode'] = 'products';
        }

        if ($rowData['parent_category']) {
            $data['parent_id'] = $rowData['parent_category']['id'];
        }

        return $data;
    }

    private function prepareCategoryAttributes(array $additionalData): array
    {
        $attributeIds = $this->credential['additional_info'][0]['filterableAttribtes'] ?? null;

        if (! empty($attributeIds)) {
            return [
                'attributes' => explode(',', $attributeIds),
            ];
        }

        $defaults = $this->getDefaultFilterableAttributeIds();

        return ! empty($defaults) ? ['attributes' => $defaults] : [];
    }

    protected function getDefaultFilterableAttributeIds(): array
    {
        if ($this->defaultFilterableAttributeIds !== null) {
            return $this->defaultFilterableAttributeIds;
        }

        $ids = [];

        try {
            $response = $this->setApiRequest(MethodType::GET->value, 'attribute', ['is_filterable' => 1]);

            foreach ((array) $response as $attribute) {
                if (! empty($attribute['id'])) {
                    $ids[] = $attribute['id'];
                }
            }
        } catch (\Throwable $e) {
            $this->jobLogger?->warning($e);
        }

        return $this->defaultFilterableAttributeIds = $ids;
    }

    public function getParentId($id = null): ?string
    {
        if (! empty($id)) {
            $mapData = $this->getMapping($this->credential['id'], $id);

            return $mapData ? $mapData->external_id : null;
        }

        return null;
    }

    /**
     * Sets category field values for a product. If an category field is not present in the given values array,
     */
    protected function setFieldsAdditionalData(array $additionalData, $filePath, $options = []): array
    {
        $fieldValues = [];
        $standardFields = $this->mappingFields['standard_field']->mapped_value ?? [];
        $fixedValue = $this->mappingFields['standard_field']->fixed_value ?? [];

        foreach ($standardFields as $key => $mappingField) {
            $field = $this->categoryFieldRepository->where('code', $mappingField)->first();
            if (in_array($field->type, [FieldValidator::FILE_FIELD_TYPE, FieldValidator::IMAGE_FIELD_TYPE])) {
                $fileFullPath = $this->getExistingFilePath($field, $additionalData);
                if ($fileFullPath) {
                    $fieldValues[$key] = $fileFullPath;
                }

                continue;
            }

            if ($field->type === FieldValidator::BOOLEAN_FIELD_TYPE) {
                $additionalData[$mappingField] = ! empty($additionalData[$mappingField]) ? 1 : 0;
            }

            $fieldValues[$key] = $additionalData[$mappingField] ?? null;
        }

        if (empty($standardFields['slug']) && ! empty($additionalData['name'])) {
            $fixedValue['slug'] = $this->createSlug($additionalData['name']);
        }

        if (isset($additionalData['logo_path']) || isset($additionalData['banner_path'])) {
            unset($additionalData['logo_path'], $additionalData['banner_path']);
        }

        if (empty($standardFields)) {
            $fieldValues = array_merge($additionalData, $fixedValue);
        }

        $fieldValues = array_merge($fieldValues, $fixedValue);

        return $fieldValues;
    }

    public function prepareCategoriesUpdataData($item): array
    {
        if (! empty($item['code'])) {
            $item['slug'] = $this->createSlug($item['code']);
        }

        $locale = $item['locale'];

        foreach ($item as $key => $value) {
            if ($key === 'logo_path' || $key === 'banner_path') {
                continue;
            }

            if (! empty($value)) {
                $item[$locale][$key] = $value;
            }
        }

        return $item;
    }

    protected function getExistingFilePath($field, $additionalData): ?string
    {
        $existingFilePath = $additionalData[$field->code] ?? null;
        if ($existingFilePath && Storage::exists($existingFilePath)) {
            return Storage::path($existingFilePath);
        }

        return null;
    }

    protected function createSlug(?string $name): ?string
    {
        return $name ? trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $name))), '-') : null;
    }
}
