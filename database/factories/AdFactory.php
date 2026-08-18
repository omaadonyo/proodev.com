<?php

namespace Database\Factories;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ad>
 */
class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'title' => fake()->catchPhrase(),
            'image_url' => null,
            'target_url' => fake()->url(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
