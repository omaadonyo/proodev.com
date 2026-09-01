<?php

namespace App\Services\Recruiter;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Central access gate for the Recruiter Intelligence Suite.
 *
 * The full intelligence suite is available to:
 *  - any user with the Recruiter role (recruiting professional), or
 *  - any user with the Company role (recruiting company team).
 *
 * Multi-tenancy workspaces additionally require a paid Recruiter or
 * Recruiter Intelligence plan for company accounts (see User::hasWorkspaceAccess).
 */
class RecruiterAccessService
{
    public function canAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasIntelligenceAccess();
    }

    public function canAccessOrAbort(?User $user): bool
    {
        return $this->canAccess($user);
    }

    public function isRecruiterRole(User $user): bool
    {
        return $user->role?->isRecruiter() ?? false;
    }

    public function planLabel(?User $user): string
    {
        if (! $user) {
            return 'Free';
        }

        if ($user->role?->isRecruiter()) {
            return 'Recruiter Intelligence Suite';
        }

        return $user->ownedCompany()?->plan?->label() ?? 'Free';
    }

    public function recruitersForCompany(int $companyId)
    {
        return User::query()
            ->where('role', UserRole::Recruiter)
            ->whereHas('companyMemberships', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
    }
}
