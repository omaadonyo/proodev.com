<?php

namespace Database\Seeders\Concerns;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVerificationStatus;
use App\Enums\RecognitionType;
use App\Enums\ReportStatus;
use App\Enums\TimelineEventType;
use App\Enums\VerificationRequestType;
use App\Enums\Visibility;
use App\Enums\VouchStatus;
use App\Enums\VouchType;
use App\Models\Comment;
use App\Models\JournalEntry;
use App\Models\News;
use App\Models\Project;
use App\Models\ProjectRecognition;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Vouch;
use App\Models\WeeklyReport;
use App\Services\ExperienceService;
use App\Services\ReputationService;
use App\Services\TimelineService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared logic for building realistic developer demo content: skills, XP,
 * streaks, projects, journal entries, vouches, verification requests and
 * weekly reports. Used by DatabaseSeeder and by the admin system reset.
 */
trait SeedsDeveloperContent
{
    /**
     * Give the user a locally-cached portrait, cycling through the portraits
     * already downloaded by UserAvatarSeeder so no network call is needed.
     */
    protected function assignCachedAvatar(User $user, int $index): void
    {
        $files = Storage::disk('public')->files('avatars/seed');

        if (count($files) === 0) {
            return;
        }

        $user->forceFill(['avatar_path' => $files[$index % count($files)]])->saveQuietly();
    }

    /**
     * @param  array<string, int>  $skills
     */
    protected function attachSkills(User $user, array $skills, int $index): void
    {
        $groups = [
            ['php', 'laravel', 'mysql'],
            ['go', 'kubernetes', 'docker'],
            ['javascript', 'vuejs', 'typescript'],
            ['php', 'laravel', 'livewire'],
            ['python', 'machine-learning', 'docker'],
            ['terraform', 'aws', 'cicd'],
            ['postgresql', 'mysql', 'redis'],
            ['php', 'security', 'testing'],
            ['rust', 'microservices', 'system-design'],
            ['typescript', 'react', 'rest-apis'],
        ];

        $slugs = $groups[$index % count($groups)];

        $user->skills()->syncWithPivotValues(
            array_map(fn ($slug) => $skills[$slug] ?? null, $slugs),
            ['level' => rand(1, 4), 'times_used' => rand(3, 30)],
        );
    }

    /**
     * Award realistic XP and streak activity for a seeded profile.
     */
    protected function awardProfileGrowth(User $user, int $index): void
    {
        $xp = [120, 460, 220, 780, 1400, 560, 320, 2200, 980, 640][$index % 10];
        $streak = [3, 9, 5, 14, 21, 8, 6, 28, 16, 11][$index % 10];

        app(ExperienceService::class)->award($user, $xp, 'Seeded growth');

        $user->forceFill([
            'streak_count' => $streak,
            'longest_streak' => $streak,
            'last_activity_at' => now()->subHours(rand(1, 24)),
        ])->saveQuietly();
    }

    /**
     * @param  array<int, User>  $profiles
     */
    protected function seedProjects(User $admin, array $profiles): void
    {
        $templates = [
            ['title' => 'Realtime Analytics Dashboard', 'tagline' => 'Live metrics streaming over WebSockets with sub-second latency.', 'tech' => ['PHP', 'Laravel', 'Livewire', 'Redis', 'Tailwind CSS'], 'problem' => 'Teams lacked a single pane of glass for live product metrics.', 'solution' => 'Built a realtime dashboard streaming events through Laravel Reverb into a reactive UI.', 'architecture' => 'Event-sourced ingestion with a Redis stream, fan-out to WebSockets, and a Livewire island per widget.', 'lessons' => 'Backpressure on the event bus and debouncing reconnects were the two biggest wins.'],
            ['title' => 'Open Source Package: Laravel Flags', 'tagline' => 'Feature flags with zero config and full audit trails.', 'tech' => ['PHP', 'Laravel', 'MySQL'], 'problem' => 'Feature flags spread across config files and databases with no audit trail.', 'solution' => 'Publishable package providing flag evaluation, targeting rules, and an audit log.', 'architecture' => 'A single flags table with JSON targeting, cached aggressively, invalidated on write.', 'lessons' => 'Cache invalidation is 95% of the job; the rest is DX.'],
            ['title' => 'CI Pipeline Optimizer', 'tagline' => 'Cut build times 40% with intelligent job scheduling.', 'tech' => ['Go', 'Docker', 'CI/CD'], 'problem' => 'Monorepo CI runs were taking 25+ minutes blocking merges.', 'solution' => 'Dependency-aware job scheduling that only runs affected packages.', 'architecture' => 'Go service reading a build graph, scheduling on a custom runner pool with cached layers.', 'lessons' => 'Leveraging Docker layer caching by topologically sorting jobs was the core insight.'],
            ['title' => 'Design System Token Engine', 'tagline' => 'Tailwind-based tokens compiled at build time for every product.', 'tech' => ['TypeScript', 'Vite', 'Tailwind CSS'], 'problem' => 'Design tokens were drifting between design and engineering.', 'solution' => 'Single source-of-truth tokens generating Tailwind configs and CSS variables.', 'architecture' => 'Token JSON → PostCSS plugin → framework-agnostic output consumed by 6 apps.', 'lessons' => 'Start with a naming convention you can defend; everything else follows.'],
            ['title' => 'Knowledge Base with AI Summaries', 'tagline' => 'Docs that summarize themselves and surface stale content.', 'tech' => ['PHP', 'Laravel', 'Livewire', 'OpenAI'], 'problem' => 'Company wiki had thousands of pages with no freshness signal.', 'solution' => 'AI-generated summaries, staleness scoring, and a digest of outdated pages.', 'architecture' => 'Background jobs generating summaries via LLM with graceful fallback, indexed for search.', 'lessons' => 'Graceful fallback to rule-based summaries kept the feature alive when the AI was down.'],
            ['title' => 'Multi-tenant Billing Gateway', 'tagline' => 'Subscription billing for a white-label SaaS platform.', 'tech' => ['PHP', 'Laravel', 'PostgreSQL'], 'problem' => 'Billing was per-customer bespoke, impossible to maintain.', 'solution' => 'Tenant-aware subscription engine with proration, trials, and webhook resilience.', 'architecture' => 'Separate billing schema per tenant with a shared reconciliation worker.', 'lessons' => 'Idempotent webhook handling is non-negotiable for payments.'],
            ['title' => 'Offline-First Mobile Companion', 'tagline' => 'Field team app that syncs when connectivity returns.', 'tech' => ['TypeScript', 'React Native', 'SQLite'], 'problem' => 'Field teams lost hours of work when signal dropped in remote sites.', 'solution' => 'Offline-first sync engine with conflict resolution and optimistic UI.', 'architecture' => 'Local SQLite store, mutation log, and a last-writer-wins sync protocol.', 'lessons' => 'Designing the conflict model up front saved months of bug chasing.'],
            ['title' => 'Payroll Data Warehouse', 'tagline' => 'Petabyte-scale reporting over a decade of payroll history.', 'tech' => ['Python', 'Spark', 'AWS'], 'problem' => 'Payroll reporting ran overnight and broke on data volume.', 'solution' => 'Columnar warehouse with incremental loads and materialized dashboards.', 'architecture' => 'Spark jobs landing Parquet into S3, queried through a SQL engine.', 'lessons' => 'Incremental loads turned a nightly batch into a 10-minute job.'],
            ['title' => 'Conversational Support Bot', 'tagline' => 'Resolves 60% of tier-1 tickets before a human sees them.', 'tech' => ['PHP', 'Laravel', 'OpenAI', 'Redis'], 'problem' => 'Support queue had 40% repeats that drained senior agents.', 'solution' => 'LLM-powered triage that answers known issues and routes the rest.', 'architecture' => 'RAG over the help center with a confidence gate that escalates to humans.', 'lessons' => 'The confidence gate matters more than the model choice.'],
            ['title' => 'Edge CDN Cache Controller', 'tagline' => 'Purging and prewarming across 200 edge locations.', 'tech' => ['Go', 'Kubernetes', 'Redis'], 'problem' => 'Cache invalidation was manual and took hours to propagate.', 'solution' => 'Control plane that fans out purge/prewarm commands to every edge.', 'architecture' => 'Pub/sub command bus with per-edge acknowledgements and retries.', 'lessons' => 'At 200 nodes, fan-out design and observability are the whole job.'],
        ];

        foreach ($profiles as $index => $user) {
            $template = $templates[$index % count($templates)];
            $publishedAt = now()->subDays(rand(3, 60));

            $project = Project::firstOrCreate(
                ['slug' => Str::slug($template['title']).'-'.($index + 1)],
                array_merge([
                    'user_id' => $user->id,
                    'title' => $template['title'],
                    'tagline' => $template['tagline'],
                    'problem' => $template['problem'],
                    'solution' => $template['solution'],
                    'architecture' => $template['architecture'],
                    'tech_stack' => $template['tech'],
                    'engineering_decisions' => [
                        'Chose '.$template['tech'][0].' for its ecosystem and hiring pool.',
                        'Prioritized a boring, well-understood stack over novelty.',
                        'Wrote integration tests around the risky 20% of the system.',
                    ],
                    'lessons_learned' => $template['lessons'],
                    'demo_url' => 'https://example.com',
                    'repository_url' => 'https://github.com/example/'.$index,
                    'status' => ProjectStatus::Published,
                    'published_at' => $publishedAt,
                    'verification_status' => ProjectVerificationStatus::Pending,
                    'views_count' => rand(20, 400),
                ]),
            );

            $recognizers = collect($profiles)->reject(fn ($u) => $u->id === $project->user_id)->take(rand(2, 5));

            foreach ($recognizers as $recognizer) {
                ProjectRecognition::firstOrCreate(
                    ['project_id' => $project->id, 'user_id' => $recognizer->id],
                    ['type' => collect(RecognitionType::cases())->random()],
                );
            }

            $project->update(['recognition_count' => $project->recognitions()->count()]);

            foreach ($recognizers->take(2) as $commenter) {
                Comment::firstOrCreate(
                    ['commentable_type' => Project::class, 'commentable_id' => $project->id, 'user_id' => $commenter->id],
                    ['body' => collect([
                        'This is exactly the kind of project writeup I needed. Great architecture section.',
                        'Really clean approach. How did you handle the cache invalidation edge case?',
                        'Bookmarking this. The lessons learned section is gold.',
                        'Solid engineering decisions. Would love to see the demo.',
                    ])->random()],
                );
            }

            app(TimelineService::class)->record(
                $project->user,
                TimelineEventType::ProjectPublished,
                "Published project: {$project->title}",
                $project->tagline,
                ['project_id' => $project->id, 'project_slug' => $project->slug, 'project_title' => $project->title, 'tagline' => $project->tagline],
                $project,
                Visibility::Public,
                $publishedAt,
            );

            if (rand(0, 1)) {
                app(TimelineService::class)->record(
                    $project->user,
                    TimelineEventType::MilestoneReached,
                    "Hit a milestone on {$project->title}",
                    'First 100 recognised engineers engaged.',
                    [],
                    $project,
                    Visibility::Public,
                    $publishedAt->copy()->addDays(2),
                );
            }
        }

        Project::firstOrCreate(
            ['slug' => 'proodev-showcase'],
            [
                'user_id' => $admin->id,
                'title' => 'ProoDev Showcase',
                'tagline' => 'The evidence platform you are looking at — built on itself.',
                'problem' => 'Engineers cannot prove their skills because reputation is self-reported and easy to fake.',
                'solution' => 'ProoDev turns real work into evidence: AI analyzes source material, scores Engineering Magnitude, and builds a passport that cannot be faked.',
                'architecture' => 'Laravel 13 + Livewire 4 + Flux UI, realtime via Reverb.',
                'tech_stack' => ['PHP', 'Laravel', 'Livewire', 'Flux UI', 'Tailwind CSS'],
                'engineering_decisions' => ['Dogfooding every feature we ship.', 'Realtime-first feed via server-side events.'],
                'status' => ProjectStatus::Published,
                'published_at' => now()->subDays(2),
                'verification_status' => ProjectVerificationStatus::Verified,
                'verified_at' => now(),
                'views_count' => 120,
                'recognition_count' => 1,
            ],
        );
    }

    /**
     * @param  array<int, User>  $profiles
     */
    protected function seedJournal(array $profiles): void
    {
        $entries = [
            ['title' => 'Refactoring the event pipeline', 'content' => "This week I finally tackled the event pipeline. The core problem was coupling: every new feature needed to know how to push events. I extracted a thin dispatcher and everything got simpler.\n\nKey takeaways:\n- Small interfaces beat big abstractions\n- Test the seams, not the internals\n- Write the failure mode first"],
            ['title' => 'Learning about backpressure', 'content' => "Diving deep into backpressure this week. Realized that in a streaming system, the slowest consumer defines the system's throughput. Started adding bounded queues everywhere and watching latency smooth out."],
            ['title' => 'Shipping the analytics dashboard', 'content' => "Wrapped up the analytics dashboard and shipped it to production. The realtime updates over WebSockets made the whole thing feel alive, and the team loved watching metrics update live during the demo.\n\nLearned a ton about event fan-out and debouncing reconnects."],
            ['title' => 'Interviewing across time zones', 'content' => 'A distributed team means interviews at odd hours. This week I paired with a candidate in Singapore at 7am my time — worth every second, the async-first workflow conversation was fantastic.'],
            ['title' => 'Tuning the CI cache', 'content' => 'Shaved 11 minutes off the build by being deliberate about Docker layer ordering and only warming the cache for packages that changed. Small wins compound.'],
        ];

        foreach ($profiles as $index => $user) {
            $entry = $entries[$index % count($entries)];

            JournalEntry::firstOrCreate(
                ['user_id' => $user->id, 'title' => $entry['title']],
                [
                    'content' => $entry['content'],
                    'visibility' => Visibility::Public,
                    'ai_processed' => true,
                    'published_at' => now()->subDays(rand(1, 14)),
                ],
            );

            app(TimelineService::class)->record(
                $user,
                TimelineEventType::JournalPublished,
                "Published journal entry: {$entry['title']}",
                null,
                [],
                null,
                Visibility::Public,
                now()->subDays(rand(1, 14)),
            );
        }
    }

    /**
     * Seed the public news page with sample announcements so the page is not
     * empty on a fresh install. Idempotent by slug.
     */
    protected function seedNews(User $admin): void
    {
        $articles = [
            [
                'title' => 'Welcome to ProoDev - Proof over claims',
                'slug' => 'welcome-to-proodev',
                'excerpt' => 'Evidence-backed engineering identity is here. Connect your real work and let AI turn it into an explainable magnitude score.',
                'body' => "We are excited to open ProoDev to the engineering community.\n\n## What ProoDev does\n\nPaste a repository, article, or project URL. ProoDev fetches the source, drafts an engineering report, maps your skills, and scores the evidence. That flows into an explainable **Engineering Magnitude** score from 0-1000.\n\n- Add evidence: GitHub, GitLab, Bitbucket, npm, Packagist, articles, talks, and project URLs.\n- AI analyzes it into an engineering report.\n- Build a public passport backed by verified work.\n\nEverything you connect is optional to share. You control what appears on your passport.\n\n## Why proof beats claims\n\nEvery engineering profile on the internet is self-reported. Anyone can claim they architect distributed systems or write clean production code. ProoDev takes the opposite approach:\n\n1. You connect the actual work - a repo, a shipped product, a published article.\n2. AI fetches and reads the source material, not the metadata.\n3. The result is an engineering report tied to evidence you can inspect.\n\nA solved problem outweighs a hundred idle commits, and the score reflects depth, not activity.\n\n## How the score is built\n\nEngineering Magnitude (0-1000) is computed across eight factors:\n\n- **Evidence quality** - is the work substantial and reproducible?\n- **Technical depth** - how the code is written, structured, and reasoned about.\n- **Knowledge sharing** - articles, talks, and documented decisions.\n- **Breadth** - variety across languages, tools, and problem domains.\n- **Consistency** - sustained contribution over time.\n- **Community trust** - vouches from engineers who have worked with you.\n- **Verification** - confirmed roles and contributions.\n- **Open source** - contributions beyond your own repositories.\n\nEvery point ties back to a specific piece of evidence. Nothing is a vibes-based estimate.\n\n## Getting started\n\n1. Create your free account.\n2. Paste your first repo or project URL.\n3. Let the AI draft your engineering report.\n4. Share your passport - public by default, private whenever you want.\n\nWe are just getting started. Expect evidence analysis to get sharper, the community to grow, and verification to become a first-class citizen of every profile.\n\nWelcome aboard - proof over claims.",
                'is_featured' => true,
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'AI match analysis is now live on open roles',
                'slug' => 'ai-match-analysis-live',
                'excerpt' => 'Every open role can now be scored against your evidence-backed profile before you apply.',
                'body' => "We shipped AI match analysis across the jobs registry.\n\nEach role is now scored against your evidence-backed profile - skills, depth, and shipped work - so you can focus on roles where you genuinely fit.\n\nScores under 100 are quick estimates; run a full analysis for a deep dive on a specific role.\n\n## How the score works\n\nThe match engine combines three signals into a single 0-100 score:\n\n1. **Skill overlap** - how many of the role's required skills appear on your profile and at what level.\n2. **Evidence relevance** - whether your shipped work resembles what the role demands day-to-day.\n3. **Depth signal** - projects, articles, and vouches that back your stated experience.\n\nA high match does not mean an interview, but it means your work already speaks the language of the role.\n\n## What changed\n\n- Every job card and detail page now shows your personal match score.\n- Scores update as your evidence grows, so improving your profile improves your matches.\n- Full analysis produces a break-down by factor, not just a single number.\n\n## Keep your edge\n\nThe best way to raise your match score is to keep shipping. Add the project you just finished, publish the article you have been meaning to write, and let your evidence do the talking.\n\nAI match analysis is free for candidates. Companies see the same evidence when you apply - proof over claims.",
                'is_featured' => false,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Introducing live chat and presence',
                'slug' => 'live-chat-and-presence',
                'excerpt' => 'Realtime messaging between verified engineers, with typing indicators and presence.',
                'body' => "Verified engineers can now chat in real time.\n\n## What's included\n\n- Realtime messaging powered by Reverb.\n- Typing indicators.\n- Presence channels so you can see who is online.\n\nStart a conversation from any passport or the leaderboard.\n\n## Why realtime matters here\n\nEngineering is a team sport. Most collaboration happens in a review thread or a side chat, and that context is lost the moment the session ends. ProoDev keeps the conversation attached to the work:\n\n- Open a chat from a passport, a project, or an evidence page.\n- Presence shows you who is actually around right now.\n- Typing indicators keep replies snappy, the way a good pairing session feels.\n\n## Respecting your focus\n\nChat is opt-in. Your online status only surfaces when you enable presence, and notifications are designed to be quiet. We built this for collaboration, not for another source of ping anxiety.\n\n## What is next\n\n- Message search across all conversations.\n- Shared snippets that pin evidence to a discussion.\n- Group threads for project teams and communities.\n\nGo say hello to someone whose work you admire. That is the point.",
                'is_featured' => false,
                'published_at' => now()->subDays(2),
            ],
        ];

        foreach ($articles as $article) {
            $news = News::firstOrNew(['slug' => $article['slug']]);

            if (! $news->exists) {
                $article['views_count'] = rand(20, 120);
            }

            $news->fill(array_merge($article, [
                'body' => $article['body'],
                'author_id' => $admin->id,
            ]))->save();
        }
    }

    /**
     * @param  array<int, User>  $profiles
     */
    protected function seedVouches(array $profiles): void
    {
        $types = [
            VouchType::Skill,
            VouchType::CodeReview,
            VouchType::Mentorship,
            VouchType::Architecture,
            VouchType::Collaboration,
        ];

        $messages = [
            'Caught a subtle race condition in review',
            'Patient mentor, clear explanations',
            'Designed a fault-tolerant retry layer',
            'Great pairing partner on the billing work',
            'Deep expertise in the domain',
            'Sharp review that improved the design',
        ];

        $count = count($profiles);

        for ($i = 0; $i < $count; $i += 3) {
            if (! isset($profiles[$i], $profiles[($i + 1) % $count])) {
                continue;
            }

            Vouch::firstOrCreate(
                ['voucher_id' => $profiles[$i]->id, 'vouchee_id' => $profiles[($i + 1) % $count]->id, 'type' => $types[$i % count($types)]],
                [
                    'message' => $messages[$i % count($messages)],
                    'status' => VouchStatus::Approved,
                    'weight' => rand(8, 15),
                ],
            );
        }

        if (isset($profiles[1], $profiles[0])) {
            Vouch::firstOrCreate(
                ['voucher_id' => $profiles[1]->id, 'vouchee_id' => $profiles[0]->id],
                [
                    'type' => VouchType::Project,
                    'message' => 'The analytics dashboard is genuinely impressive.',
                    'status' => VouchStatus::Pending,
                    'weight' => 8,
                ],
            );
        }
    }

    /**
     * @param  array<int, User>  $profiles
     */
    protected function seedVerifications(array $profiles): void
    {
        foreach ([0, 2, 4, 7, 12, 25] as $index) {
            if (! isset($profiles[$index])) {
                continue;
            }

            VerificationRequest::firstOrCreate(
                ['user_id' => $profiles[$index]->id, 'type' => VerificationRequestType::Company],
                [
                    'company_name' => ['Acme Corp', 'Globex', 'Initech'][$index % 3],
                    'company_domain' => 'example.com',
                    'label' => 'Senior Engineer',
                    'status' => 'approved',
                    'reviewed_at' => now()->subDays(10),
                    'expires_at' => now()->addYears(2),
                ],
            );
        }

        if (isset($profiles[3])) {
            VerificationRequest::firstOrCreate(
                ['user_id' => $profiles[3]->id, 'type' => VerificationRequestType::PublicContribution],
                [
                    'label' => 'Laravel',
                    'evidence' => ['Community recognized contributions'],
                    'status' => 'pending',
                ],
            );
        }
    }

    /**
     * @param  array<int, User>  $profiles
     */
    protected function seedReports(array $profiles): void
    {
        $project = Project::first();

        if (! isset($profiles[0]) || ! $project) {
            return;
        }

        Report::firstOrCreate(
            ['reporter_id' => $profiles[0]->id, 'reportable_type' => Project::class, 'reportable_id' => $project->id],
            [
                'reason' => 'Plagiarized project description',
                'details' => 'The solution text appears copied from a public repository README.',
                'status' => ReportStatus::Open,
            ],
        );
    }

    /**
     * @param  array<int, User>  $profiles
     */
    protected function seedWeeklyReports(array $profiles): void
    {
        foreach (array_slice($profiles, 0, 8) as $user) {
            WeeklyReport::firstOrCreate(
                ['user_id' => $user->id, 'week_started' => now()->startOfWeek()->subWeek()],
                [
                    'data' => [
                        'xp_earned' => rand(20, 120),
                        'projects_published' => rand(0, 1),
                        'days_active' => rand(3, 7),
                        'summary' => 'Solid week of building. Kept the streak alive and made progress on the event pipeline.',
                    ],
                    'generated_at' => now()->startOfWeek(),
                ],
            );
        }
    }

    /**
     * Recalculate reputation for every profile so passports and rankings are
     * consistent with the freshly seeded content.
     *
     * @param  array<int, User>  $profiles
     */
    protected function recalculateReputations(array $profiles): void
    {
        foreach ($profiles as $user) {
            app(ReputationService::class)->recalculate($user);
        }
    }
}
