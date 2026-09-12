<?php

namespace App\Rules;

use App\Services\YandexMaps\OrganizationUrlParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexMapsOrgUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('Введите корректную ссылку.');

            return;
        }

        $organizationId = (new OrganizationUrlParser())->extractOrganizationId($value);

        if ($organizationId === null) {
            $fail('Ссылка не похожа на карточку организации в Яндекс.Картах. Ожидается вид: https://yandex.ru/maps/org/название/123456789/');
        }
    }
}
