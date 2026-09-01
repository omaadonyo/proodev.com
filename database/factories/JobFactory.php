<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by' => null,
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'requirements' => [fake()->sentence(), fake()->sentence(), fake()->sentence()],
            'location' => fake()->randomElement(['Remote', 'San Francisco, US', 'London, UK', 'Berlin, DE']),
            'is_remote' => true,
            'employment_type' => fake()->randomElement(['full-time', 'contract', 'part-time', 'internship']),
            'salary_min' => fake()->randomElement([null, 60000, 80000, 100000]),
            'salary_max' => fake()->randomElement([null, 120000, 140000, 180000]),
            'currency' => 'USD',
            'status' => JobStatus::Open,
            'published_at' => now(),
            'deadline' => fake()->optional(0.6)->dateTimeBetween('+2 weeks', '+8 weeks'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Closed,
        ]);
    }
}
