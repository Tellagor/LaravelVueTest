<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'external_id' => $this->faker->unique()->uuid(),
            'author_name' => $this->faker->name(),
            'rating' => $this->faker->numberBetween(1, 5),
            'text' => $this->faker->sentence(),
            'published_at' => $this->faker->dateTimeBetween('-1 year'),
        ];
    }
}
