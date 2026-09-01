<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\TimelineEventType;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Company;
use App\Models\CompanyMember;
use App\Models\User;
use App\Services\TimelineService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $company = ($input['role'] ?? 'developer') === 'company';

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'company_name' => ['nullable', 'required_if:role,company', 'string', 'max:120'],
        ], [
            'company_name.required_if' => 'Please add your company name.',
        ])->validate();

        $username = $input['username'] ?? $this->generateUsername($input['name']);

        $user = User::create([
            'name' => $input['name'],
            'username' => $username,
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $company ? UserRole::Company : UserRole::Developer,
        ]);

        app(TimelineService::class)->record(
            $user,
            TimelineEventType::Joined,
            'Joined ProoDev',
            'Welcome to your evidence-backed engineering identity.',
            [],
            visibility: Visibility::Public,
        );

        if ($company) {
            $this->createCompany($user, $input['company_name']);
        }

        return $user;
    }

    private function createCompany(User $owner, string $name): void
    {
        $company = Company::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'plan' => CompanyPlan::Trial,
            'status' => CompanyStatus::Approved,
            'approved_at' => now(),
        ]);

        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
    }

    public function generateUsername(string $name): string
    {
        $base = Str::slug($name, '_');

        if ($base === '') {
            $base = 'engineer';
        }

        $username = $base;
        $suffix = 2;

        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.$suffix++;
        }

        return Str::lower($username);
    }
}
