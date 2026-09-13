<?php

namespace App\Exceptions\YandexMaps;

use RuntimeException;

class RateLimitedException extends RuntimeException
{
    public function __construct(int $status)
    {
        parent::__construct("Яндекс Карты дали ответ, указывающим на ограничение частоты запросов или срабатывание защиты от ботов ({$status}) — back off and retry later");
    }
}
