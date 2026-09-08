<?php

namespace Webkul\Bagisto\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CredentialUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'shop_url'            => ['required', 'url', 'unique:wk_bagisto_credential,shop_url,'.$this->id],
            'email'               => ['required', 'email'],
            'password'            => ['required'],
            'store_info'          => ['array'],
            'filterableAttribtes' => ['nullable'],
        ];
    }
}
