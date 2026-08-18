<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'user_id' => User::factory(),
            'status' => ApplicationStatus::Pending,
            'cover_letter' => fake()->paragraphs(2, true),
        ];
    }

    public function shortlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Shortlisted,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Rejected,
            'reviewed_at' => now(),
        ]);
    }

    public function hired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Hired,
            'reviewed_at' => now(),
        ]);
    }
}
