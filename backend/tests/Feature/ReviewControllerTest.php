<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reviews(): void
    {
        $this->getJson('/api/organization/reviews')->assertUnauthorized();
    }

    public function test_it_returns_empty_list_when_user_has_no_organization(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/organization/reviews');

        $response->assertOk();
        $response->assertJson(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
    }

    public function test_it_returns_paginated_reviews_ordered_by_published_at(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->for($user)->create();

        Review::factory()->for($organization)->create([
            'external_id' => 'old',
            'published_at' => now()->subDays(5),
        ]);
        Review::factory()->for($organization)->create([
            'external_id' => 'new',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/organization/reviews');

        $response->assertOk();
        $response->assertJsonPath('data.0.external_id', 'new');
        $response->assertJsonPath('data.1.external_id', 'old');
        $response->assertJsonPath('meta.total', 2);
    }
}
