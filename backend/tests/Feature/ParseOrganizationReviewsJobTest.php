<?php

namespace Tests\Feature;

use App\Exceptions\YandexMaps\RateLimitedException;
use App\Exceptions\YandexMaps\SourceLayoutChangedException;
use App\Jobs\ParseOrganizationReviews;
use App\Models\Organization;
use App\Models\Review;
use App\Services\YandexMaps\YandexMapsScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ParseOrganizationReviewsJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeScrapeResult(array $overrides = []): array
    {
        return array_merge([
            'yandexId' => '1042189213',
            'name' => null,
            'ratingAvg' => 4.7,
            'ratingsCount' => 1490,
            'reviewsCount' => 586,
            'reviews' => [
                [
                    'externalId' => 'rev-1',
                    'authorName' => 'Иван',
                    'rating' => 5,
                    'text' => 'Отлично',
                    'publishedAt' => '2026-01-01T00:00:00.000Z',
                ],
            ],
        ], $overrides);
    }

    private function mockScraper(): Mockery\MockInterface
    {
        return Mockery::mock(YandexMapsScraper::class);
    }

    public function test_it_stores_results_on_success(): void
    {
        $organization = Organization::factory()->create(['status' => 'pending']);

        $scraper = $this->mockScraper();
        $scraper->shouldReceive('scrape')->once()->andReturn($this->fakeScrapeResult());

        (new ParseOrganizationReviews($organization))->handle($scraper);

        $organization->refresh();

        $this->assertSame('ready', $organization->status);
        $this->assertEquals(4.7, (float) $organization->rating_avg);
        $this->assertSame(1490, $organization->ratings_count);
        $this->assertSame(586, $organization->reviews_count);
        $this->assertNull($organization->last_error);

        $this->assertDatabaseHas('reviews', [
            'organization_id' => $organization->id,
            'external_id' => 'rev-1',
            'rating' => 5,
        ]);

        $this->assertDatabaseHas('organization_snapshots', [
            'organization_id' => $organization->id,
            'ratings_count' => 1490,
        ]);

        $this->assertDatabaseHas('parsing_runs', [
            'organization_id' => $organization->id,
            'status' => 'success',
            'reviews_fetched' => 1,
        ]);
    }

    public function test_it_upserts_reviews_without_duplicating_on_repeated_runs(): void
    {
        $organization = Organization::factory()->create();

        $scraper = $this->mockScraper();
        $scraper->shouldReceive('scrape')->twice()->andReturn($this->fakeScrapeResult());

        (new ParseOrganizationReviews($organization))->handle($scraper);
        (new ParseOrganizationReviews($organization))->handle($scraper);

        $this->assertSame(1, Review::where('organization_id', $organization->id)->count());
        $this->assertSame(2, $organization->snapshots()->count());
    }

    public function test_it_marks_the_run_as_failed_and_rethrows_on_a_generic_error(): void
    {
        $organization = Organization::factory()->create();

        $scraper = $this->mockScraper();
        $scraper->shouldReceive('scrape')->once()->andThrow(
            new SourceLayoutChangedException('markup changed'),
        );

        $this->expectException(SourceLayoutChangedException::class);

        try {
            (new ParseOrganizationReviews($organization))->handle($scraper);
        } finally {
            $this->assertDatabaseHas('parsing_runs', [
                'organization_id' => $organization->id,
                'status' => 'failed',
            ]);
        }
    }

    public function test_it_releases_the_job_instead_of_failing_on_rate_limit(): void
    {
        $organization = Organization::factory()->create();

        $scraper = $this->mockScraper();
        $scraper->shouldReceive('scrape')->once()->andThrow(new RateLimitedException(429));

        $job = (new ParseOrganizationReviews($organization))->withFakeQueueInteractions();
        $job->handle($scraper);

        $job->assertReleased();

        $organization->refresh();
        $this->assertNotSame('failed', $organization->status);

        $this->assertDatabaseHas('parsing_runs', [
            'organization_id' => $organization->id,
            'status' => 'failed',
        ]);
    }

    public function test_failed_hook_marks_the_organization_as_failed(): void
    {
        $organization = Organization::factory()->create(['status' => 'processing']);

        (new ParseOrganizationReviews($organization))->failed(new SourceLayoutChangedException('boom'));

        $organization->refresh();

        $this->assertSame('failed', $organization->status);
        $this->assertStringContainsString('boom', $organization->last_error);
    }
}
