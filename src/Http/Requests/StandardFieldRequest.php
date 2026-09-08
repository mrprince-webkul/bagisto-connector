<?php

namespace Webkul\Bagisto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StandardFieldRequest extends FormRequest
{
    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('standard_category_fields')) {
            $decoded = json_decode($this->input('standard_category_fields'), true);
            $fixedValues = array_filter($this->input('standard_category_fields_default'), function ($value) {
                return $value !== '';
            });
            if (is_array($decoded)) {
                $this->merge($decoded);
                $this->merge($fixedValues);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $bagistoFields = config('bagisto-category-fields');

        $rules = [];

        foreach ($bagistoFields as $field) {
            $validationRules = [];

            if ($field['required']) {
                $validationRules[] = [
                    'required',
                    'string',
                ];

                $rules[$field['code']] = $validationRules;
            }
        }

        return $rules;
    }
}
