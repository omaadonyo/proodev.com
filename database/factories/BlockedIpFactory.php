<?php

namespace Database\Factories;

use App\Models\BlockedIp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedIp>
 */
class BlockedIpFactory extends Factory
{
    protected $model = BlockedIp::class;

    public function definition(): array
    {
        return [
            'ip_address' => fake()->ipv4(),
            'reason' => null,
            'blocked_by' => User::factory(),
        ];
    }
}
