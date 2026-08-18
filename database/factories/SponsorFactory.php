<?php

namespace Database\Factories;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'logo_url' => null,
            'website_url' => fake()->url(),
            'tagline' => fake()->catchPhrase(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
