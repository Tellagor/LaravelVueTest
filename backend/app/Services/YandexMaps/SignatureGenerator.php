<?php

namespace App\Services\YandexMaps;

class SignatureGenerator
{
    public function generate(string $queryString): string
    {
        $hash = 5381;
        $length = strlen($queryString);

        for ($i = 0; $i < $length; $i++) {
            $hash = ((33 * $hash) ^ ord($queryString[$i])) & 0xFFFFFFFF;
        }

        return (string) $hash;
    }
}
