<?php

namespace App\Exceptions\YandexMaps;

use RuntimeException;

class OrganizationNotFoundException extends RuntimeException
{
    public function __construct(string $url, int $status)
    {
        parent::__construct("Страница организации не загрузилась  (HTTP {$status}): {$url}");
    }
}
