<?php

namespace Webkul\Bagisto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StandardAttributeRequest extends FormRequest
{
    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('standard_attributes')) {
            $decoded = json_decode($this->input('standard_attributes'), true);
            $fixedValue = $this->input('standard_attributes_default');
            if (is_array($decoded)) {
                $this->merge($decoded);
                $this->merge($fixedValue);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $bagistoAttributes = config('bagisto-attributes');

        $rules = [];

        foreach ($bagistoAttributes as $attribute) {
            $validationRules = [];

            if ($attribute['required']) {
                $validationRules[] = [
                    'required',
                    'string',
                ];

                $rules[$attribute['code']] = $validationRules;
            }
        }

        return $rules;
    }
}
