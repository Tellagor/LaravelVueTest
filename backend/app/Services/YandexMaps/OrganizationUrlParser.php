<?php

namespace App\Services\YandexMaps;

class OrganizationUrlParser
{
    public function extractOrganizationId(string $url): ?string
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['host']) || empty($parts['path'])) {
            return null;
        }

        if (! $this->isYandexHost($parts['host'])) {
            return null;
        }

        if (! preg_match('#/maps/(?:[^/]+/)*org/[^/]+/(\d+)#', $parts['path'], $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function isYandexHost(string $host): bool
    {
        return (bool) preg_match('/^([a-z0-9-]+\.)*yandex\.[a-z.]+$/i', $host);
    }
}
