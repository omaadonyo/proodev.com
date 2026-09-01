<?php

namespace Database\Factories;

use App\Models\PassportView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PassportView>
 */
class PassportViewFactory extends Factory
{
    protected $model = PassportView::class;

    public function definition(): array
    {
        return [
            'passport_owner_id' => User::factory(),
            'viewer_id' => User::factory(),
            'ip_address' => null,
            'viewed_at' => now(),
        ];
    }
}
