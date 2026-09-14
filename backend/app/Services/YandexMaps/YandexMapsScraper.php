<?php

namespace App\Services\YandexMaps;

use App\Exceptions\YandexMaps\OrganizationNotFoundException;
use App\Exceptions\YandexMaps\RateLimitedException;
use App\Exceptions\YandexMaps\SourceLayoutChangedException;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

class YandexMapsScraper
{
    private const PAGE_SIZE = 50;

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    public function __construct(
        private readonly OrganizationUrlParser $urlParser,
        private readonly SignatureGenerator $signatureGenerator,
    ) {
    }

    public function scrape(string $url, int $targetReviewCount = 600): array
    {
        $organizationId = $this->urlParser->extractOrganizationId($url);

        $cookieJar = new CookieJar;
        $reviewsPageUrl = $this->buildReviewsPageUrl($organizationId);

        [$csrfToken, $summary] = $this->bootstrapSession($reviewsPageUrl, $cookieJar);

        $reviews = [];
        $sessionId = $this->generateSessionId();
        $reqId = $this->generateReqId();
        $maxPages = (int) max(1, ceil(min($targetReviewCount, $summary['reviewsCount'] ?: $targetReviewCount) / self::PAGE_SIZE));

        for ($page = 1; $page <= $maxPages && count($reviews) < $targetReviewCount; $page++) {
            $pageReviews = $this->fetchReviewsPage(
                $organizationId,
                $csrfToken,
                $page,
                $sessionId,
                $reqId,
                $cookieJar,
                $reviewsPageUrl,
            );

            if ($pageReviews === null) {
                [$csrfToken] = $this->bootstrapSession($reviewsPageUrl, $cookieJar);

                $pageReviews = $this->fetchReviewsPage(
                    $organizationId,
                    $csrfToken,
                    $page,
                    $sessionId,
                    $reqId,
                    $cookieJar,
                    $reviewsPageUrl,
                ) ?? [];
            }

            if ($pageReviews === []) {
                break;
            }

            foreach ($pageReviews as $review) {
                $reviews[$review['externalId']] = $review;
            }

            if (count($pageReviews) < self::PAGE_SIZE) {
                break;
            }

            usleep(random_int(400_000, 900_000));
        }

        return [
            'yandexId' => $organizationId,
            'name' => $summary['name'],
            'ratingAvg' => $summary['ratingAvg'],
            'ratingsCount' => $summary['ratingsCount'],
            'reviewsCount' => $summary['reviewsCount'],
            'reviews' => array_values($reviews),
        ];
    }

    private function httpOptions(CookieJar $cookieJar): array
    {
        $options = ['cookies' => $cookieJar];

        if ($proxyUrl = config('services.yandex_maps.proxy_url')) {
            $options['proxy'] = $proxyUrl;
        }

        return $options;
    }

    private function bootstrapSession(string $reviewsPageUrl, CookieJar $cookieJar): array
    {
        $response = Http::withOptions($this->httpOptions($cookieJar))
            ->withUserAgent(self::USER_AGENT)
            ->withHeaders(['Accept-Language' => 'ru-RU,ru;q=0.9'])
            ->timeout(20)
            ->get($reviewsPageUrl);

        if ($response->status() === 429 || $response->status() === 403) {
            throw new RateLimitedException($response->status());
        }

        if ($response->failed()) {
            throw new OrganizationNotFoundException($reviewsPageUrl, $response->status());
        }

        $html = $response->body();

        $csrfToken = $this->extractCsrfToken($html);

        if (! $csrfToken) {
            throw new SourceLayoutChangedException('csrfToken not found in the organization page HTML');
        }

        $summary = $this->extractAggregateRating($html);

        if (! $summary) {
            throw new SourceLayoutChangedException('AggregateRating schema.org block not found in the organization page HTML');
        }

        $summary['name'] = $this->extractOrganizationName($html);

        return [$csrfToken, $summary];
    }

    private function fetchReviewsPage(
        string $organizationId,
        string $csrfToken,
        int $page,
        string $sessionId,
        string $reqId,
        CookieJar $cookieJar,
        string $referer,
    ): ?array {
        $params = [
            'ajax' => '1',
            'businessId' => $organizationId,
            'csrfToken' => $csrfToken,
            'locale' => 'ru_RU',
            'page' => (string) $page,
            'pageSize' => (string) self::PAGE_SIZE,
            'ranking' => 'by_time',
            'reqId' => $reqId,
            'sessionId' => $sessionId,
        ];

        $signature = $this->signatureGenerator->generate(http_build_query($params));

        $response = Http::withOptions($this->httpOptions($cookieJar))
            ->withUserAgent(self::USER_AGENT)
            ->withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $referer,
            ])
            ->timeout(20)
            ->get('https://yandex.ru/maps/api/business/fetchReviews', [...$params, 's' => $signature]);

        if ($response->status() === 429 || $response->status() === 403) {
            throw new RateLimitedException($response->status());
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new SourceLayoutChangedException('fetchReviews response was not valid JSON');
        }

        if (isset($data['csrfToken']) && ! isset($data['data'])) {
            return null;
        }

        $rawReviews = $data['data']['reviews'] ?? null;

        if (! is_array($rawReviews)) {
            throw new SourceLayoutChangedException('fetchReviews response did not contain a reviews array');
        }

        return array_map(fn (array $review) => [
            'externalId' => (string) $review['reviewId'],
            'authorName' => $review['author']['name'] ?? 'Аноним',
            'rating' => (int) $review['rating'],
            'text' => $review['text'] ?? null,
            'publishedAt' => $review['updatedTime'] ?? null,
        ], $rawReviews);
    }

    private function buildReviewsPageUrl(string $organizationId): string
    {
        return "https://yandex.ru/maps/org/{$organizationId}/reviews/";
    }

    private function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/"csrfToken"\s*:\s*"([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractAggregateRating(string $html): ?array
    {
        if (! preg_match('/itemProp="aggregateRating"[^>]*>((?:<meta[^>]*>)+)/', $html, $blockMatch)) {
            return null;
        }

        preg_match_all('/<meta itemProp="([a-zA-Z]+)" content="([^"]*)"\s*\/?>/', $blockMatch[1], $metas, PREG_SET_ORDER);

        $values = [];
        foreach ($metas as $meta) {
            $values[$meta[1]] = $meta[2];
        }

        if (! isset($values['ratingValue'], $values['ratingCount'], $values['reviewCount'])) {
            return null;
        }

        return [
            'ratingAvg' => round((float) $values['ratingValue'], 1),
            'ratingsCount' => (int) $values['ratingCount'],
            'reviewsCount' => (int) $values['reviewCount'],
        ];
    }

    private function extractOrganizationName(string $html): ?string
    {
        if (preg_match('/<meta property="og:title" content="Отзывы о «([^»]+)»/u', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function generateSessionId(): string
    {
        $timestamp = (int) round(microtime(true) * 1000);

        return $timestamp.'_'.random_int(100000, 999999);
    }

    private function generateReqId(): string
    {
        $timestamp = (int) round(microtime(true) * 1000);

        return $timestamp.random_int(100, 999).'-'.random_int(100000000, 999999999).'-sas1-'.random_int(1000, 9999);
    }
}
