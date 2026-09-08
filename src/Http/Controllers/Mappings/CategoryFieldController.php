<?php

namespace Webkul\Bagisto\Http\Controllers\Mappings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Http\Requests\StandardFieldRequest;
use Webkul\Bagisto\Repositories\CategoryFieldMappingRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;

class CategoryFieldController extends Controller
{
    public function __construct(
        protected CategoryFieldRepository $categoryFieldRepository,
        protected CategoryFieldMappingRepository $categoryFieldMappingRepository,
    ) {}

    public function index(): View
    {
        $bagistoCategoryFields = config('bagisto-category-fields');

        $bagistoCategoryFields = $this->translate($bagistoCategoryFields);

        $categoryFields = $this->categoryFieldRepository->all();

        $mappedCategoryFields = $this->categoryFieldMappingRepository->findByField('section', 'standard_field')->first();

        return view('bagisto::export.mappings.categoryfields.index', compact('bagistoCategoryFields', 'categoryFields', 'mappedCategoryFields'));
    }

    /**
     * Handles the process of storing or updating attribute mappings.
     */
    public function storeOrUpdate(StandardFieldRequest $request): JsonResponse
    {
        try {
            $formatedData = $this->setFormatForMapping(request()->all());

            $standardAttributes = $this->categoryFieldMappingRepository->findByField('section', 'standard_field')->first();

            if (! empty($formatedData['standard_field']['fixed_value'])) {
                $formatedData['standard_field']['fixed_value'] = $this->sortJsonKeysCustom($formatedData['standard_field']['fixed_value']);
            }
            if ($standardAttributes) {
                $standardAttributes->update($formatedData['standard_field']);
            } else {
                $this->categoryFieldMappingRepository->create($formatedData['standard_field']);
            }

            Cache::forget(CacheType::CATEGORY_FIELD_MAPPING->value);

            return new JsonResponse([
                'message' => trans('bagisto::app.bagisto.bagisto-category-fields.success-message'),
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Arrange Data for the attribute mapping
     */
    private function setFormatForMapping(array $data): array
    {
        $formatedData = [];

        $standardCategoryFields = json_decode($data['standard_category_fields'], true) ?? [];

        $standardCategoryFieldsDefault = $data['standard_category_fields_default'] ?? [];
        $fixedValue = [];

        $fixedValue = array_filter($standardCategoryFieldsDefault, function ($value) {
            return $value !== '';
        });

        /** Format for standard category fields */
        $formatedData['standard_field'] = [
            'section'      => 'standard_field',
            'mapped_value' => $standardCategoryFields,
            'fixed_value'  => $fixedValue,
        ];

        return $formatedData;
    }

    public function translate(array $arrayData): array
    {
        foreach ($arrayData as $key => $value) {
            $arrayData[$key]['name'] = trans($value['name']);
            $arrayData[$key]['title'] = trans($value['title']);
        }

        return $arrayData;
    }

    protected function sortJsonKeysCustom(array $data): array
    {
        uksort($data, function ($a, $b) {
            $lenA = strlen($a);
            $lenB = strlen($b);

            if ($lenA !== $lenB) {
                return $lenA <=> $lenB;
            }

            return strcmp($a, $b);
        });

        return $data;
    }
}
