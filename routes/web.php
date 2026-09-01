<?php

use App\Enums\CompanyStatus;
use App\Http\Controllers\ApplicationResumeController;
use App\Http\Controllers\BillingExportController;
use App\Http\Controllers\CompaniesLandingController;
use App\Http\Controllers\EmailPreviewController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\SocialiteController;
use App\Models\Company;
use App\Models\Evidence;
use App\Models\Job;
use App\Models\JournalEntry;
use App\Models\News;
use App\Models\Project;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vouch;
use App\Services\GeoLocationService;
use App\Support\FeatureFlags;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;

$landing = function (): array {
    $publicUsers = fn ($q) => $q->where('is_admin', false);
    $publicEvents = fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_admin', false));

    return [
        'stats' => [
            ['value' => number_format(User::visibleToPublic()->count()), 'label' => 'Engineers verified'],
            ['value' => number_format(Evidence::ready()->count()), 'label' => 'Evidence analyzed'],
            ['value' => number_format(Project::published()->count()), 'label' => 'Projects shipped'],
            ['value' => number_format(Vouch::where('status', 'approved')->whereHas('voucher', $publicUsers)->count()), 'label' => 'Vouches earned'],
        ],
        'feed' => TimelineEvent::with(['user', 'target'])->public()->where($publicEvents)->latestVisiblePerUser(null)->latest('occurred_at')->limit(6)->get(),
        'projects' => Project::published()->with('user')->latest('published_at')->limit(3)->get(),
        'journal' => JournalEntry::with('user')->publiclyVisible()->latest('published_at')->limit(3)->get(),
        'evidence' => Evidence::ready()->with('user')->latest('analyzed_at')->limit(3)->get(),
        'engineers' => User::visibleToPublic()->where('reputation_score', '>', 0)->orderByDesc('reputation_score')->limit(6)->get(),
        'vouches' => Vouch::where('status', 'approved')->whereHas('voucher', $publicUsers)->with(['voucher', 'vouchee', 'skill'])->latest()->limit(6)->get(),
        'openJobs' => Job::open()->with('company')->latest('published_at')->limit(6)->get(),
        'onlineCount' => User::visibleToPublic()->where('last_activity_at', '>', now()->subMinutes(5))->count(),
        'liveUsers' => User::visibleToPublic()->where('last_activity_at', '>', now()->subMinutes(10))->orderByDesc('last_activity_at')->limit(5)->get(),
        'globeDevelopers' => User::visibleToPublic()->where('public_passport', true)
            ->whereNotNull('location')
            ->orderByDesc('reputation_score')
            ->limit(200)
            ->get()
            ->map(function (User $developer) {
                $coords = GeoLocationService::resolve($developer->location);

                if ($coords === null) {
                    return null;
                }

                return [
                    'id' => $developer->id,
                    'name' => $developer->name,
                    'handle' => $developer->handle(),
                    'headline' => $developer->headline,
                    'location' => $developer->location,
                    'reputation' => (int) $developer->reputation_score,
                    'avatar' => $developer->avatarUrl(),
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ];
            })
            ->filter()
            ->values()
            ->slice(0, 120)
            ->all(),
        'features' => [
            ['key' => 'feed', 'icon' => 'bolt', 'title' => 'Live Feed', 'description' => 'A real-time stream of evidence being added, analyzed, and verified across the community.', 'href' => route('home')],
            ['key' => 'evidence', 'icon' => 'document-text', 'title' => 'Evidence Library', 'description' => 'Paste any repo, article, or project URL. AI fetches the source, drafts an engineering report, and scores it - evidence, not claims.', 'href' => route('devid', auth()->user()?->handle() ?? 'user-1')],
            ['key' => 'projects', 'icon' => 'folder', 'title' => 'Projects', 'description' => 'Publish projects with problem framing, solution docs, and a public portfolio that backs your identity.', 'href' => route('projects.index')],
            ['key' => 'journal', 'icon' => 'book-open', 'title' => 'Engineering Journal', 'description' => "Capture what you learned, the decisions you made, and the mistakes you won't repeat - as evidence.", 'href' => route('journal.index')],
            ['key' => 'reputation', 'icon' => 'shield-check', 'title' => 'Magnitude & DevID', 'description' => 'An explainable Engineering Magnitude score from 0-1000 and a public DevID that proves your work.', 'href' => route('devid', auth()->user()?->handle() ?? 'user-1')],
        ],
        'steps' => [
            ['number' => '01', 'title' => 'Add evidence', 'description' => 'Paste a GitHub repo, article, or project URL. No self-reported claims - just real work you can point to.', 'href' => route('devid', auth()->user()?->handle() ?? 'user-1')],
            ['number' => '02', 'title' => 'AI analyzes it', 'description' => 'ProoDev fetches the source, drafts an engineering report, maps your skills, and scores the evidence.', 'href' => route('devid', auth()->user()?->handle() ?? 'user-1')],
            ['number' => '03', 'title' => 'Build your magnitude', 'description' => 'Evidence flows into an explainable Engineering Magnitude score from 0-1000, factor by factor.', 'href' => route('devid', auth()->user()?->handle() ?? 'user-1')],
            ['number' => '04', 'title' => 'Prove it publicly', 'description' => 'A shareable DevID backed by verified evidence, community vouches, and real shipped work.', 'href' => route('home')],
        ],
        'faqs' => [
            ['question' => 'Is ProoDev really free to use?', 'answer' => 'Yes. Adding evidence, running AI analysis, building your Engineering Magnitude, and sharing your public DevID are all free. Your evidence and identity are yours to keep.'],
            ['question' => 'What is Engineering Magnitude?', 'answer' => 'Engineering Magnitude is an explainable 0-1000 score computed from your evidence across eight factors - evidence quality, technical depth, knowledge sharing, breadth, consistency, community trust, verification, and open-source contribution. Every point is tied to real evidence.'],
            ['question' => 'How is this different from a resume or self-reported profile?', 'answer' => 'ProoDev is evidence-first. Instead of listing claims, you connect the work that backs them - repositories, articles, shipped projects - and AI reads and analyzes that material directly. Claims have to point to proof.'],
            ['question' => 'Can I keep my work private?', 'answer' => 'Absolutely. Every piece of evidence, project, and journal entry can be public or private. You control what appears on your DevID, and only public evidence powers your discoverability.'],
            ['question' => 'What kinds of evidence can I add?', 'answer' => 'GitHub, GitLab, and Bitbucket repositories, npm/Packagist packages, articles, talks, videos, and general project URLs. ProoDev classifies the source, fetches it, and drafts an engineering report with AI.'],
            ['question' => 'Who can see my DevID?', 'answer' => 'DevIDs are public by default so the community can find collaborators and verify your work, but you can switch to private anytime. You choose exactly what to show - evidence, projects, skills, and vouches.'],
        ],
    ];
};

// Transactional email previews — available to any authenticated account
// (developers, recruiters, companies) so the team can review layouts in
// the browser without needing admin access.
Route::middleware('auth')->group(function () {
    Route::get('/emails/preview', [EmailPreviewController::class, 'index'])->name('emails.preview');
    Route::get('/emails/preview/{mail}', [EmailPreviewController::class, 'show'])->name('emails.preview.show');
});

$companiesLanding = function (): array {
    $presenceEnabled = FeatureFlags::publicPresenceEnabled();

    // A substantial, filterable pool (not just the reputation leaders) so the
    // live evidence search feels real: all visible engineers, ranked by
    // reputation, capped at 60. The marquee below draws from the same pool.
    $pool = User::visibleToPublic()
        ->with('skills')
        ->orderByDesc('reputation_score')
        ->limit(60)
        ->get();

    $engineers = $pool->map(fn (User $user) => [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'headline' => $user->headline,
        'location' => $user->location,
        'avatar' => $user->avatarUrl(),
        'verified' => $user->isVerified(),
        'online' => $presenceEnabled && $user->isOnline(),
        'skills' => $user->skills->pluck('name')->take(4)->all(),
    ])
        ->values()
        ->all();

    return [
        'engineers' => $engineers,
        'skillFilters' => collect($engineers)->flatMap(fn ($engineer) => $engineer['skills'])->unique()->sort()->values()->all(),
        'engineersCount' => number_format(User::visibleToPublic()->count()),
        'evidenceCount' => number_format(Evidence::ready()->count()),
        'openJobsCount' => number_format(Job::open()->count()),
        'onlineCount' => User::visibleToPublic()->where('last_activity_at', '>', now()->subMinutes(5))->count(),
        'engineersMarquee' => $pool,
        'openJobs' => Job::open()->with('company')->latest('published_at')->limit(4)->get(),
    ];
};

Route::get('/', function () use ($landing) {
    return view('landing', $landing());
})->name('welcome');

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/cookies', 'legal.cookies')->name('cookies');

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => route('welcome'), 'lastmod' => now()->toDateString(), 'changefreq' => 'daily', 'priority' => '1.0'],
        ['loc' => route('developers'), 'changefreq' => 'daily', 'priority' => '0.9'],
        ['loc' => route('for-companies'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => route('pricing'), 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['loc' => route('news.index'), 'changefreq' => 'daily', 'priority' => '0.8'],
        ['loc' => route('companies.index'), 'changefreq' => 'daily', 'priority' => '0.6'],
        ['loc' => route('jobs.index'), 'changefreq' => 'daily', 'priority' => '0.6'],
        ['loc' => route('privacy'), 'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => route('terms'), 'changefreq' => 'monthly', 'priority' => '0.3'],
        ['loc' => route('cookies'), 'changefreq' => 'monthly', 'priority' => '0.3'],
    ];

    foreach (News::published()->latest('published_at')->get() as $article) {
        $urls[] = [
            'loc' => route('news.show', $article),
            'lastmod' => $article->published_at?->toDateString(),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];
    }

    foreach (Company::where('status', CompanyStatus::Approved)->latest('approved_at')->get() as $company) {
        $urls[] = ['loc' => route('companies.show', $company), 'changefreq' => 'weekly', 'priority' => '0.6'];
    }

    foreach (Job::open()->whereHas('company', fn ($q) => $q->where('status', CompanyStatus::Approved))->latest('published_at')->get() as $job) {
        $urls[] = ['loc' => route('jobs.show', ['company' => $job->company, 'job' => $job]), 'changefreq' => 'weekly', 'priority' => '0.6'];
    }

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/for-developers', function () {
    return view('for-developers', [
        'engineersCount' => number_format(User::visibleToPublic()->count()),
        'openJobsCount' => number_format(Job::open()->count()),
    ]);
})->name('developers');

// Legacy URL — kept working, redirects to the canonical developer page.
Route::redirect('/developers', '/for-developers', 301);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->where('provider', implode('|', SocialiteController::PROVIDERS))
    ->name('login.social');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->where('provider', implode('|', SocialiteController::PROVIDERS))
    ->name('login.social.callback');

Route::livewire('/devid/{user:username}', 'pages::devid')->name('devid');

// Legacy passport URL — kept working, redirects to the canonical DevID page.
Route::redirect('/passport/{user:username}', '/devid/{user:username}', 301)->name('passport');

// Short shareable link for verified developers — resolves via short_domain,
// then falls back to username, and lands on the DevID page.
Route::get('/p/{slug}', function (string $slug) {
    $user = User::where('short_domain', $slug)->first()
        ?? User::where('username', $slug)->first();

    if (! $user) {
        throw (new ModelNotFoundException)->setModel(User::class, [$slug]);
    }

    return redirect()->route('devid', $user->handle());
})->name('passport.short');

Route::bind('user', function (string $value) {
    $user = User::where('username', $value)->first();

    if (! $user && str_starts_with($value, 'user-') && is_numeric(substr($value, 5))) {
        $user = User::find((int) substr($value, 5));
    }

    if (! $user) {
        throw (new ModelNotFoundException)->setModel(User::class, [$value]);
    }

    return $user;
});

Route::get('/for-companies', function () use ($companiesLanding) {
    return view('companies-landing', $companiesLanding());
})->name('for-companies');

Route::post('/for-companies/match-skills', [CompaniesLandingController::class, 'matchSkills'])
    ->middleware('throttle:20,1')
    ->name('for-companies.match-skills');

Route::livewire('/pricing', 'pages::pricing')->name('pricing');

// Payment gateway webhook (no auth — called by gateways).
Route::post('/payments/notify/{payment}', [PaymentCallbackController::class, 'notify'])
    ->withoutMiddleware('csrf')
    ->name('payments.notify');

Route::livewire('/companies', 'pages::companies.index')->name('companies.index');
Route::livewire('/companies/create', 'pages::companies.create')
    ->middleware('auth')
    ->name('companies.create');
Route::livewire('/companies/{company:slug}', 'pages::companies.show')->name('companies.show');
Route::livewire('/jobs', 'pages::jobs.index')->name('jobs.index');
Route::livewire('/jobs/{company:slug}/{job:slug}', 'pages::jobs.show')->name('jobs.show');

Route::livewire('/news', 'pages::news.index')->name('news.index');
Route::livewire('/news/{article:slug}', 'pages::news.show')->name('news.show');

Route::livewire('/onboarding', 'pages::onboarding')
    ->middleware('auth')
    ->name('onboarding');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/home', 'pages::feed')->name('home');
    Route::livewire('/dashboard', 'pages::feed')->name('dashboard');

    Route::get('/reputation', fn () => redirect()->route('devid', auth()->user()->handle()))->name('reputation');
    Route::get('/growth', fn () => redirect()->route('devid', auth()->user()->handle()))->name('growth');

    Route::livewire('/projects', 'pages::projects.index')->name('projects.index');
    Route::livewire('/projects/create', 'pages::projects.create')->name('projects.create');
    Route::livewire('/projects/{project:slug}', 'pages::projects.show')->name('projects.show');
    Route::livewire('/projects/{project:slug}/edit', 'pages::projects.edit')->name('projects.edit');

    Route::livewire('/evidence/{evidence}', 'pages::evidence.show')->name('evidence.show');

    Route::livewire('/journal', 'pages::journal.index')->name('journal.index');
    Route::livewire('/journal/create', 'pages::journal.create')->name('journal.create');
    Route::livewire('/journal/{entry}', 'pages::journal.show')->name('journal.show');

    Route::livewire('/vouches', 'pages::vouches')->name('vouches');

    Route::livewire('/notifications', 'pages::notifications')->name('notifications');

    Route::livewire('/companies/{company:slug}/manage', 'pages::companies.manage')->name('companies.manage');
    Route::livewire('/companies/{company:slug}/applicants', 'pages::companies.applicants')->name('companies.applicants');
    Route::livewire('/companies/{company:slug}/dashboard', 'pages::companies.dashboard')->name('companies.dashboard');
    Route::livewire('/companies/{company:slug}/onboarding', 'pages::companies.onboarding')->name('companies.onboarding');
    Route::livewire('/companies/{company:slug}/jobs/create', 'pages::companies.jobs.create')->name('companies.jobs.create');
    Route::livewire('/companies/{company:slug}/jobs/{job:slug}/edit', 'pages::companies.jobs.edit')->name('companies.jobs.edit');

    Route::livewire('/jobs/{company:slug}/{job:slug}/apply', 'pages::jobs.apply')->name('jobs.apply');
    Route::livewire('/applications', 'pages::applications.index')->name('applications.index');
    Route::get('/applications/{application}/resume', ApplicationResumeController::class)->name('applications.resume');

    Route::livewire('/credits', 'pages::credits')->name('credits');
    Route::livewire('/verify', 'pages::verify')->name('verify');
    Route::livewire('/billing', 'pages::billing')->name('billing');

    Route::get('/billing/export/csv', [BillingExportController::class, 'csv'])->name('billing.export.csv');
    Route::get('/billing/export/pdf', [BillingExportController::class, 'pdf'])->name('billing.export.pdf');

    // Printable invoices & receipts — download (print/save as PDF) or email a copy.
    Route::get('/invoices/{payment}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{payment}/email', [InvoiceController::class, 'email'])->name('invoices.email');
    // Auto-scan merged into the credits feature — keep the old URL working.
    Route::redirect('/auto-scan', '/credits')->name('auto-scan');
    Route::livewire('/checkout/{payment}', 'pages::checkout')->name('checkout');

    Route::get('/payments/{payment}/checkout', [PaymentCallbackController::class, 'checkout'])->name('payments.checkout');
    Route::get('/payments/{payment}/simulate', [PaymentCallbackController::class, 'simulate'])->name('payments.simulate');

    Route::livewire('/subscription', 'pages::subscription')->name('subscription');

    Route::livewire('/workspaces', 'pages::workspaces')->name('workspaces');

    // Recruiter Intelligence Suite - gated behind recruiter role or paid company plan.
    Route::middleware('recruiter.access')->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::livewire('/', 'pages::recruiter.index')->name('index');
        Route::livewire('/candidates/{candidate}', 'pages::recruiter.candidates.show')->name('candidates.show');
        Route::livewire('/compare', 'pages::recruiter.compare')->name('compare');
        Route::livewire('/search', 'pages::recruiter.search')->name('search');
        Route::livewire('/rankings', 'pages::recruiter.rankings')->name('rankings');
        Route::livewire('/validate', 'pages::recruiter.validate')->name('validate');
        Route::livewire('/interviews', 'pages::recruiter.interviews')->name('interviews');
        Route::livewire('/exports/{candidate}', 'pages::recruiter.exports')->name('exports');
        Route::livewire('/workspace', 'pages::recruiter.workspace')->name('workspace');
        Route::livewire('/alerts', 'pages::recruiter.alerts')->name('alerts');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/analytics', 'pages::admin.analytics')->name('analytics');
        Route::livewire('/verifications', 'pages::admin.verifications')->name('verifications');
        Route::livewire('/users', 'pages::admin.users')->name('users');
        Route::livewire('/vouches', 'pages::admin.vouches')->name('vouches');
        Route::livewire('/reports', 'pages::admin.reports')->name('reports');
        Route::livewire('/plagiarism', 'pages::admin.plagiarism')->name('plagiarism');
        Route::livewire('/companies', 'pages::admin.companies')->name('companies');
        Route::livewire('/payments', 'pages::admin.payments')->name('payments');
        Route::livewire('/sales', 'pages::admin.sales')->name('sales');
        Route::get('/sales/export', [BillingExportController::class, 'ledger'])->name('sales.export');
        Route::get('/sales/export/pdf', [BillingExportController::class, 'salesReport'])->name('sales.export.pdf');
        Route::livewire('/subscriptions', 'pages::admin.subscriptions')->name('subscriptions');
        Route::livewire('/auto-scan', 'pages::admin.auto-scan')->name('auto-scan');
        Route::livewire('/ai', 'pages::admin.ai')->name('ai');
        Route::livewire('/ads', 'pages::admin.ads')->name('ads');
        Route::livewire('/sponsors', 'pages::admin.sponsors')->name('sponsors');
        Route::livewire('/settings/news', 'pages::admin.settings.news')->name('settings.news');
        Route::livewire('/settings/seo', 'pages::admin.settings.seo')->name('settings.seo');
        Route::livewire('/settings/social', 'pages::admin.settings.social')->name('settings.social');
        Route::livewire('/settings/backups', 'pages::admin.settings.backups')->name('settings.backups');
        Route::livewire('/settings/system', 'pages::admin.settings.system')->name('settings.system');
        Route::redirect('/settings', '/admin/settings/seo')->name('settings');
        Route::livewire('/', 'pages::admin.index')->name('index');
    });
});

require __DIR__.'/settings.php';
