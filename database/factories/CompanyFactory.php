<?php

namespace Database\Factories;

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->unique()->company(),
            'description' => fake()->paragraph(),
            'industry' => fake()->randomElement(['SaaS', 'Fintech', 'E-commerce', 'Healthtech', 'Developer Tools', 'AI']),
            'location' => fake()->city().', '.fake()->country(),
            'size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
            'plan' => CompanyPlan::Trial,
            'job_post_credits' => 1,
            'is_pioneer' => false,
            'status' => CompanyStatus::Approved,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompanyStatus::Pending,
            'approved_at' => null,
        ]);
    }

    public function intelligence(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => CompanyPlan::Intelligence,
            'job_post_credits' => 3,
        ]);
    }

    public function recruiter(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => CompanyPlan::Recruiter,
            'job_post_credits' => 3,
        ]);
    }
}
