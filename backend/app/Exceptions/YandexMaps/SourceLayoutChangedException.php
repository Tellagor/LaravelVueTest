<?php

namespace App\Exceptions\YandexMaps;

use RuntimeException;

class SourceLayoutChangedException extends RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct("Структура страницы Яндекс Карт изменена, парсер не может продолжить: {$reason}");
    }
}
