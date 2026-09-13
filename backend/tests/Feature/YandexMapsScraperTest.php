<?php

namespace Tests\Feature;

use App\Exceptions\YandexMaps\RateLimitedException;
use App\Exceptions\YandexMaps\SourceLayoutChangedException;
use App\Services\YandexMaps\YandexMapsScraper;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexMapsScraperTest extends TestCase
{
    private const ORG_URL = 'https://yandex.ru/maps/org/test-cafe/123456789/';

    private function fakeOrgPageHtml(bool $withCsrf = true, bool $withAggregateRating = true): string
    {
        $csrf = $withCsrf ? '<script>var opts = {"csrfToken":"abc123hash:1789300000"};</script>' : '';

        $aggregateRating = $withAggregateRating
            ? '<span itemScope itemProp="aggregateRating" itemType="http://schema.org/AggregateRating">'
                .'<meta itemProp="reviewCount" content="1"/>'
                .'<meta itemProp="ratingCount" content="10"/>'
                .'<meta itemProp="bestRating" content="5"/>'
                .'<meta itemProp="worstRating" content="1"/>'
                .'<meta itemProp="ratingValue" content="4.5"/>'
                .'</span>'
            : '';

        return '<html><head><meta property="og:title" content="Отзывы о «Тест Кафе» на улице Тестовой"></head>'
            ."<body>{$csrf}{$aggregateRating}</body></html>";
    }

    private function fakeReviewsResponse(): array
    {
        return [
            'data' => [
                'reviews' => [
                    [
                        'reviewId' => 'rev-1',
                        'author' => ['name' => 'Иван'],
                        'rating' => 5,
                        'text' => 'Отлично',
                        'updatedTime' => '2026-01-01T00:00:00.000Z',
                    ],
                ],
            ],
        ];
    }

    public function test_it_scrapes_rating_summary_and_reviews(): void
    {
        Http::fake([
            'https://yandex.ru/maps/org/*' => Http::response($this->fakeOrgPageHtml(), 200),
            'https://yandex.ru/maps/api/business/fetchReviews*' => Http::response($this->fakeReviewsResponse(), 200),
        ]);

        $result = app(YandexMapsScraper::class)->scrape(self::ORG_URL);

        $this->assertSame('123456789', $result['yandexId']);
        $this->assertSame('Тест Кафе', $result['name']);
        $this->assertSame(4.5, $result['ratingAvg']);
        $this->assertSame(10, $result['ratingsCount']);
        $this->assertSame(1, $result['reviewsCount']);
        $this->assertCount(1, $result['reviews']);
        $this->assertSame('rev-1', $result['reviews'][0]['externalId']);
        $this->assertSame('Иван', $result['reviews'][0]['authorName']);
        $this->assertSame(5, $result['reviews'][0]['rating']);
    }

    public function test_it_throws_rate_limited_exception_on_429(): void
    {
        Http::fake([
            'https://yandex.ru/maps/org/*' => Http::response('limited', 429),
        ]);

        $this->expectException(RateLimitedException::class);

        app(YandexMapsScraper::class)->scrape(self::ORG_URL);
    }

    public function test_it_throws_when_csrf_token_is_missing(): void
    {
        Http::fake([
            'https://yandex.ru/maps/org/*' => Http::response($this->fakeOrgPageHtml(withCsrf: false), 200),
        ]);

        $this->expectException(SourceLayoutChangedException::class);

        app(YandexMapsScraper::class)->scrape(self::ORG_URL);
    }

    public function test_it_throws_when_aggregate_rating_block_is_missing(): void
    {
        Http::fake([
            'https://yandex.ru/maps/org/*' => Http::response($this->fakeOrgPageHtml(withAggregateRating: false), 200),
        ]);

        $this->expectException(SourceLayoutChangedException::class);

        app(YandexMapsScraper::class)->scrape(self::ORG_URL);
    }
}
