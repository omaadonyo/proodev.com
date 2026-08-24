<?php

namespace App\Services;

use App\Models\User;
use Database\Seeders\DemoReseedSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Wipes demo/dummy content and rebuilds a clean, presentable state: every
 * account except the single platform admin (adonyo@proodev.com) is removed,
 * analytics are cleared, and 50 realistic engineers from around the world are
 * reseeded with full records (skills, XP, projects, vouches, verifications).
 */
class SystemResetService
{
    /**
     * Tables whose rows are fully purged on reset. Reference/config data
     * (skills, achievements, feature flags, payment settings) is intentionally
     * preserved so the reseeded demo keeps working.
     *
     * @var list<string>
     */
    protected array $purgeTables = [
        // Wirechat — messages and conversations first, then groups/invites.
        'wirechat_attachments',
        'wirechat_messages',
        'wirechat_participants',
        'wirechat_message_requests',
        'wirechat_conversations',
        'wirechat_join_requests',
        'wirechat_invites',
        'wirechat_groups',
        'wirechat_actions',
        'wirechat_settings',
        // Company / jobs / applications.
        'applications',
        'resume_validations',
        'job_matches',
        'job_posts',
        'jobs',
        'company_members',
        'companies',
        'team_profiles',
        // Transactions.
        'payments',
        'credit_transactions',
        // Evidence / projects / content.
        'evidence_analyses',
        'evidence',
        'project_recognitions',
        'projects',
        'comments',
        'journal_entries',
        'vouches',
        'reports',
        'plagiarism_strikes',
        'repo_claims',
        'auto_scan_runs',
        'auto_scan_urls',
        'verification_requests',
        'user_verifications',
        'timeline_events',
        'weekly_reports',
        'passport_views',
        'notifications',
        'audit_logs',
        'candidate_intelligence_reports',
        'recruiter_interviews',
        'recruiter_matches',
        'recruiter_notes',
        'recruiter_placements',
        'talent_alerts',
        'talent_pool_members',
        'talent_pools',
        'workspace_members',
        'workspaces',
        // Paid placements / marketing.
        'ads',
        'sponsors',
        // Analytics & access control.
        'blocked_ips',
        // Reminder toggles tied to deleted conversations.
        'chat_reminder_mutes',
    ];

    /**
     * Run the reset. Operation is not wrapped in a transaction because SQLite
     * ignores `foreign_keys OFF` inside one; FKs are disabled for the duration
     * and re-enabled in a finally block so a mid-run failure never leaves the
     * connection in a lax state.
     *
     * @return array{users_removed: int, users_reseeded: int, accounts_kept: int, tables: int}
     */
    public function reset(): array
    {
        $admin = $this->ensurePlatformAdmin();

        $removedUsers = $this->removeAllUsersExcept($admin->id);

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            foreach ($this->purgeTables as $table) {
                DB::table($table)->delete();
            }

            // Clear every session except the one driving this request so the
            // admin stays logged in while all other sessions are wiped.
            $currentSession = session()->getId();

            DB::table('sessions')
                ->when($currentSession, fn ($query) => $query->where('id', '!=', $currentSession))
                ->delete();
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->reindexSequences();

        app(DemoReseedSeeder::class)->run();

        return [
            'users_removed' => $removedUsers,
            'users_reseeded' => DemoReseedSeeder::DEFAULT_COUNT,
            'accounts_kept' => User::count(),
            'tables' => count($this->purgeTables),
        ];
    }

    /**
     * Snapshot of the current data volumes for the admin confirmation UI.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $userCounts = User::toBase()
            ->selectRaw('role, is_admin, count(*) as total')
            ->groupBy('role', 'is_admin')
            ->get()
            ->keyBy(fn ($row) => ($row->is_admin ? 'admin' : ($row->role ?: 'unknown')));

        $developers = User::where('role', 'developer')->where('is_admin', false);
        $developersWithPhoto = (clone $developers)->whereNotNull('avatar_path')->count();
        $developersTotal = (clone $developers)->count();

        return [
            'users' => User::count(),
            'admins' => (int) ($userCounts['admin']->total ?? 0),
            'developers' => $developersTotal,
            'developers_with_photo' => $developersWithPhoto,
            'users_removed' => max(0, User::count() - 1),
            'companies' => (int) DB::table('companies')->count(),
            'jobs' => (int) DB::table('job_posts')->count(),
            'applications' => (int) DB::table('applications')->count(),
            'payments' => (int) DB::table('payments')->count(),
            'credit_transactions' => (int) DB::table('credit_transactions')->count(),
            'evidence' => (int) DB::table('evidence')->count(),
            'projects' => (int) DB::table('projects')->count(),
            'vouches' => (int) DB::table('vouches')->count(),
            'chats' => (int) DB::table('wirechat_conversations')->count(),
            'messages' => (int) DB::table('wirechat_messages')->count(),
            'tables' => count($this->purgeTables),
        ];
    }

    /**
     * The only account that survives the reset: the platform admin.
     */
    protected function ensurePlatformAdmin(): User
    {
        $admin = User::firstOrCreate(
            ['email' => config('platform.admin_email')],
            [
                'name' => 'ProoDev Admin',
                'username' => 'proodev-admin',
                'password' => Hash::make(config('platform.admin_password')),
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        $admin->forceFill([
            'name' => $admin->name ?: 'ProoDev Admin',
            'username' => $admin->username ?: 'proodev-admin',
            'email_verified_at' => now(),
            'is_admin' => true,
        ])->save();

        return $admin;
    }

    /**
     * Remove every account except the platform admin, along with its dependent
     * rows so the deletion never violates foreign keys.
     */
    protected function removeAllUsersExcept(int $adminId): int
    {
        $remove = User::where('id', '!=', $adminId)->pluck('id');

        if ($remove->isEmpty()) {
            return 0;
        }

        $removedCount = $remove->count();
        $removeChunks = $remove->chunk(500);

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            foreach ($removeChunks as $chunk) {
                $ids = $chunk->all();

                DB::table('user_skills')->whereIn('user_id', $ids)->delete();
                DB::table('user_achievements')->whereIn('user_id', $ids)->delete();
                DB::table('passkeys')->whereIn('user_id', $ids)->delete();
                DB::table('notifications')->whereIn('notifiable_id', $ids)->delete();
                DB::table('sessions')->whereIn('user_id', $ids)->delete();
                DB::table('password_reset_tokens')->whereIn(
                    'email',
                    User::whereIn('id', $ids)->pluck('email')->all(),
                )->delete();

                User::whereIn('id', $ids)->delete();
            }
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        return $removedCount;
    }

    /**
     * Reset SQLite auto-increment counters so new ids start near 1 again.
     */
    protected function reindexSequences(): void
    {
        $tables = array_map(
            fn ($t) => "'".str_replace("'", "''", $t)."'",
            $this->purgeTables,
        );

        try {
            DB::statement('DELETE FROM sqlite_sequence WHERE name IN ('.implode(',', $tables).')');
        } catch (\Throwable) {
            // some tables have no auto-increment — not worth failing the reset
        }
    }
}
