<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $id = (string) $this->faker->unique()->numberBetween(100000000, 999999999);

        return [
            'user_id' => User::factory(),
            'yandex_url' => "https://yandex.ru/maps/org/test/{$id}/",
            'yandex_id' => $id,
            'status' => 'pending',
        ];
    }
}
