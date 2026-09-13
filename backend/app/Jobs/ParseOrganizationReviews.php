<?php

namespace App\Jobs;

use App\Exceptions\YandexMaps\RateLimitedException;
use App\Models\Organization;
use App\Models\ParsingRun;
use App\Models\Review;
use App\Services\YandexMaps\YandexMapsScraper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ParseOrganizationReviews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public Organization $organization)
    {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(YandexMapsScraper $scraper): void
    {
        $run = ParsingRun::create([
            'organization_id' => $this->organization->id,
            'status' => 'running',
            'attempt' => $this->attempts(),
            'started_at' => now(),
        ]);

        $this->organization->update(['status' => 'processing']);

        try {
            $data = $scraper->scrape($this->organization->yandex_url);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            if ($exception instanceof RateLimitedException) {
                $this->release($this->backoff()[$this->attempts() - 1] ?? end($this->backoff()));

                return;
            }

            throw $exception;
        }

        $this->storeResults($data);

        $run->update([
            'status' => 'success',
            'reviews_fetched' => count($data['reviews']),
            'finished_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->organization->update([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
        ]);
    }

    private function storeResults(array $data): void
    {
        $this->organization->update([
            'name' => $data['name'] ?? $this->organization->name,
            'rating_avg' => $data['ratingAvg'],
            'ratings_count' => $data['ratingsCount'],
            'reviews_count' => $data['reviewsCount'],
            'status' => 'ready',
            'last_error' => null,
            'last_parsed_at' => now(),
        ]);

        $this->organization->snapshots()->create([
            'rating_avg' => $data['ratingAvg'],
            'ratings_count' => $data['ratingsCount'],
            'reviews_count' => $data['reviewsCount'],
            'captured_at' => now(),
        ]);

        $rows = collect($data['reviews'])->map(fn (array $review) => [
            'organization_id' => $this->organization->id,
            'external_id' => $review['externalId'],
            'author_name' => $review['authorName'],
            'rating' => $review['rating'],
            'text' => $review['text'],
            'published_at' => $review['publishedAt'] ? Carbon::parse($review['publishedAt']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($rows !== []) {
            Review::upsert(
                $rows,
                ['organization_id', 'external_id'],
                ['author_name', 'rating', 'text', 'published_at', 'updated_at'],
            );
        }
    }
}
