<?php

namespace App\Http\Requests;

use App\Rules\YandexMapsOrgUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'yandex_url' => ['required', 'string', 'max:2048', new YandexMapsOrgUrl()],
        ];
    }
}
