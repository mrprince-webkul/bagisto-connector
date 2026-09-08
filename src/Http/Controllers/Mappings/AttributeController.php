<?php

namespace Webkul\Bagisto\Http\Controllers\Mappings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Enums\Export\CacheType;
use Webkul\Bagisto\Http\Requests\StandardAttributeRequest;
use Webkul\Bagisto\Repositories\AttributeMappingRepository;

class AttributeController extends Controller
{
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeMappingRepository $attributeMappingRepository,
    ) {}

    public function index(): View
    {
        $bagistoAttributes = config('bagisto-attributes');

        $bagistoAttributes = $this->translate($bagistoAttributes);

        $attributes = $this->attributeRepository->all();

        $standardAttributes = $this->attributeMappingRepository->findByField('section', 'standard_attribute')->first();

        $additionalAttributes = $this->attributeMappingRepository->findByField('section', 'additional_attribute')->first()?->mapped_value ?? [];

        $configurableAttributes = json_encode($this->getConfigurableAttributes());
        $configurableAttributesDb = $standardAttributes->additional_info ?? [];
        $configurableSelectedAttributes = explode(',', ! empty($configurableAttributesDb['configurable_attribute']) ? $configurableAttributesDb['configurable_attribute'] : null);

        return view('bagisto::export.mappings.attributes.index', compact(
            'bagistoAttributes',
            'attributes',
            'standardAttributes',
            'additionalAttributes',
            'configurableAttributes',
            'configurableSelectedAttributes'
        ));
    }

    /**
     * Handles the process of storing or updating attribute mappings.
     */
    public function storeOrUpdate(StandardAttributeRequest $request): JsonResponse
    {
        try {
            $formatedData = $this->setFormatForMapping(request()->all());

            $standardAttributes = $this->attributeMappingRepository->findByField('section', 'standard_attribute')->first();

            if (! empty($formatedData['standard_attribute']['fixed_value'])) {
                $formatedData['standard_attribute']['fixed_value'] = $this->sortJsonKeysCustom($formatedData['standard_attribute']['fixed_value']);
            }

            if ($standardAttributes) {
                $standardAttributes->update($formatedData['standard_attribute']);
            } else {
                $this->attributeMappingRepository->create($formatedData['standard_attribute']);
            }

            Cache::forget(CacheType::ATTRIBUTE_MAPPING->value);

            return new JsonResponse([
                'message' => trans('bagisto::app.bagisto.bagisto-attributes.success-message'),
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

        $standardAttributes = json_decode($data['standard_attributes'], true) ?? [];

        $standardAttributesDefault = $data['standard_attributes_default'] ?? [];

        $fixedValue = [];

        $fixedValue = array_filter($standardAttributesDefault, function ($value) {
            return $value !== '';
        });

        $configurableAttribute['configurable_attribute'] = $data['configurable_attribute'] ?? null;

        /** Format for standard attributes */
        $formatedData['standard_attribute'] = [
            'section'         => 'standard_attribute',
            'mapped_value'    => $standardAttributes,
            'fixed_value'     => $fixedValue,
            'additional_info' => $configurableAttribute,
        ];

        return $formatedData;
    }

    public function addAdditionalAttributes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string',
            'type' => 'required|string',
        ]);

        $bagistoAttributes = config('bagisto-attributes');
        if (in_array($data['code'], array_column($bagistoAttributes, 'code'))) {
            return new JsonResponse([
                'message' => 'duplicate Attribute.',
            ], 400);
        }

        $additionalAttributes = [
            'code'  => $data['code'],
            'name'  => ucfirst($data['code']),
            'type'  => $data['type'],
        ];

        $formatedData = [
            'section' => 'additional_attribute',
        ];

        $additionalAttributeObj = $this->attributeMappingRepository->findByField('section', 'additional_attribute')->first();
        if ($additionalAttributeObj) {
            $count = is_array($additionalAttributeObj['mapped_value']) ? count($additionalAttributeObj['mapped_value']) : 0;
            if ($count) {
                foreach ($additionalAttributeObj['mapped_value'] as $key => $value) {
                    $formatedData['mapped_value'][] = $value;
                }
            }
            $formatedData['mapped_value'][] = $additionalAttributes;
            $additionalAttributeObj->update($formatedData);
        } else {
            $formatedData['mapped_value'][] = $additionalAttributes;
            $this->attributeMappingRepository->create($formatedData);
        }

        return response()->json(['message' => 'Attribute added successfully.']);
    }

    public function removeAdditionalAttributes(Request $request): void
    {
        $additionalAttributeObj = $this->attributeMappingRepository->findByField('section', 'additional_attribute')->first();
        if ($additionalAttributeObj) {
            if ($additionalAttributeObj['mapped_value']) {
                $formatedData['mapped_value'] = [];
                foreach ($additionalAttributeObj['mapped_value'] as $value) {
                    if ($value['code'] == $request->code) {
                        continue;
                    }
                    $formatedData['mapped_value'][] = $value;
                }
                $additionalAttributeObj->update($formatedData);
            }
        }

        $standardAttributeObj = $this->attributeMappingRepository->findByField('section', 'standard_attribute')->first();
        if ($standardAttributeObj) {
            if ($standardAttributeObj['mapped_value']) {
                $formatedData = [
                    'mapped_value' => [],
                    'fixed_value'  => [],
                ];

                foreach ($standardAttributeObj['mapped_value'] as $bagistoCode => $unopimCode) {
                    if ($bagistoCode == $request->code) {
                        continue;
                    }
                    $formatedData['mapped_value'][$bagistoCode] = $unopimCode;
                }
                foreach ($standardAttributeObj['fixed_value'] as $bagistoCode => $fixedValue) {
                    if ($bagistoCode == $request->code) {
                        continue;
                    }
                    $formatedData['fixed_value'][$bagistoCode] = $fixedValue;
                }

                $standardAttributeObj->update($formatedData);
            }
        }
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

    protected function getConfigurableAttributes(): array
    {
        $configurableFamily = $this->attributeFamilyRepository->all();

        $configurableAttributes = [];
        foreach ($configurableFamily as $family) {
            foreach ($family->getConfigurableAttributes() as $attribute) {
                $data = [
                    'id'   => $attribute->id,
                    'code' => $attribute->code,
                    'name' => ! empty($attribute->name) ? $attribute->name : $attribute->code,
                ];
                if (! in_array($data, $configurableAttributes)) {
                    $configurableAttributes[] = $data;
                }
            }
        }

        return $configurableAttributes;
    }
}
