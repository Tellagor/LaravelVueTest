<?php

namespace Tests\Feature;

use App\Jobs\ParseOrganizationReviews;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function test_guest_cannot_access_organization_endpoints(): void
    {
        $this->getJson('/api/organization')->assertUnauthorized();
        $this->postJson('/api/organization', ['yandex_url' => 'https://yandex.ru/maps/org/x/1/'])
            ->assertUnauthorized();
    }

    public function test_it_rejects_a_url_that_is_not_a_yandex_maps_organization_card(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/organization', [
            'yandex_url' => 'https://example.com/foo',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('yandex_url');
        $this->assertDatabaseCount('organizations', 0);
    }

    public function test_it_rejects_missing_url(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/organization', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('yandex_url');
    }

    public function test_it_saves_a_valid_organization_url_and_extracts_the_yandex_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/organization', [
            'yandex_url' => 'https://yandex.ru/maps/org/kinoteatr_mir/1042189213/',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('organization.yandex_id', '1042189213');
        $response->assertJsonPath('organization.status', 'pending');

        $this->assertDatabaseHas('organizations', [
            'user_id' => $user->id,
            'yandex_id' => '1042189213',
            'status' => 'pending',
        ]);

        Bus::assertDispatched(
            ParseOrganizationReviews::class,
            fn (ParseOrganizationReviews $job) => $job->organization->yandex_id === '1042189213',
        );
    }

    public function test_it_rejects_missing_url_and_does_not_dispatch_a_parse_job(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/organization', [])->assertUnprocessable();

        Bus::assertNotDispatched(ParseOrganizationReviews::class);
    }

    public function test_resubmitting_the_same_organization_updates_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/organization', [
            'yandex_url' => 'https://yandex.ru/maps/org/kinoteatr_mir/1042189213/',
        ])->assertCreated();

        $response = $this->postJson('/api/organization', [
            'yandex_url' => 'https://yandex.ru/maps/org/kinoteatr_mir/1042189213/?ll=1,2',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('organizations', 1);
        $this->assertSame(
            Organization::first()->id,
            $response->json('organization.id'),
        );
    }

    public function test_it_returns_null_when_the_user_has_no_organization_yet(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/organization')
            ->assertOk()
            ->assertJson(['organization' => null]);
    }

    public function test_it_returns_the_current_user_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/organization')
            ->assertOk()
            ->assertJsonPath('organization.id', $organization->id);
    }
}
