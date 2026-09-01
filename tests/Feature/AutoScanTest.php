<?php

use App\Console\Commands\AutoScanReposCommand;
use App\Enums\EvidenceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\TimelineEventType;
use App\Models\Evidence;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Project;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Services\AutoScanService;
use App\Services\BillingService;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeGithubRepos(): void
{
    Http::fake([
        'api.github.com/users/scanner/repos*' => Http::response([
            [
                'name' => 'framework',
                'full_name' => 'scanner/framework',
                'description' => 'A PHP framework for fast APIs',
                'language' => 'PHP',
                'stargazers_count' => 100,
                'forks_count' => 40,
                'topics' => ['framework', 'php'],
                'homepage' => 'https://framework.dev',
                'html_url' => 'https://github.com/scanner/framework',
                'size' => 5000,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2020-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-06-01T00:00:00Z',
            ],
            [
                'name' => 'notes',
                'full_name' => 'scanner/notes',
                'description' => 'My personal engineering notes',
                'language' => 'JavaScript',
                'stargazers_count' => 0,
                'forks_count' => 0,
                'topics' => ['notes'],
                'homepage' => null,
                'html_url' => 'https://github.com/scanner/notes',
                'size' => 500,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2022-05-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z',
                'pushed_at' => '2023-01-01T00:00:00Z',
            ],
        ], 200),
        'api.github.com/repos/scanner/*/readme' => Http::response([
            'content' => base64_encode("# Project\n\nA documented project built with PHP."),
        ], 200),
    ]);
}

function fakeSingleRepo(): void
{
    Http::fake([
        'api.github.com/repos/scanner/framework' => Http::response([
            'name' => 'framework',
            'full_name' => 'scanner/framework',
            'description' => 'A PHP framework for fast APIs',
            'language' => 'PHP',
            'stargazers_count' => 100,
            'forks_count' => 40,
            'topics' => ['framework', 'php'],
            'homepage' => 'https://framework.dev',
            'html_url' => 'https://github.com/scanner/framework',
            'size' => 5000,
            'fork' => false,
            'archived' => false,
            'default_branch' => 'main',
            'created_at' => '2020-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'pushed_at' => '2024-06-01T00:00:00Z',
        ], 200),
        'api.github.com/repos/scanner/framework/readme' => Http::response([
            'content' => base64_encode("# Framework\n\nA documented project built with PHP."),
        ], 200),
    ]);
}

test('guests are redirected from the auto-scan URL to login', function () {
    $this->get(route('auto-scan'))->assertRedirect(route('login'));
});

test('the auto-scan URL redirects to the merged credits page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('auto-scan'))
        ->assertRedirect(route('credits'));
});

test('the credits page shows the merged auto-scan section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('credits'))
        ->assertOk()
        ->assertSee('Credits & Auto-Scan')
        ->assertSee('Activate auto-scan');
});

test('purchasing auto-scan from the credits page creates a pending payment and goes to checkout', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->call('purchaseAutoScan');

    $payment = Payment::where('user_id', $user->id)
        ->where('purpose', PaymentPurpose::AutoScan)
        ->where('status', PaymentStatus::Pending)
        ->first();

    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(8.0)
        ->and($payment->metadata['interval_days'])->toBe(30);
});

test('an auto-scan payment completes checkout and activates the subscription', function () {
    $user = User::factory()->create();
    $payment = app(BillingService::class)->createAutoScanPayment($user);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    $this->actingAs($user)
        ->get(route('payments.simulate', $payment))
        ->assertRedirect(route('credits'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($user->fresh()->autoScanActive())->toBeTrue();
});

test('confirming an auto-scan payment activates the subscription', function () {
    $user = User::factory()->create();

    $payment = app(BillingService::class)->createAutoScanPayment($user);
    app(BillingService::class)->markPaid($payment);

    $user = $user->fresh();

    expect($user->auto_scan_enabled)->toBeTrue()
        ->and($user->auto_scan_active_until)->not->toBeNull()
        ->and($user->auto_scan_active_until->isFuture())->toBeTrue()
        ->and($user->autoScanActive())->toBeTrue();
});

test('an active auto-scan shows the URL queue form on the credits page', function () {

    $user = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Auto-scan is active')
        ->assertSee('Scan any URL')
        ->assertSee('Scan now');
});

test('adding repository URLs queues them for scanning', function () {
    $user = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->set('newUrl', 'https://github.com/scanner/framework')
        ->call('addUrl')
        ->assertHasNoErrors();

    expect($user->autoScanUrls()->pluck('url'))->toContain('https://github.com/scanner/framework')
        ->and($user->autoScanUrls()->first()->status)->toBe('queued');
});

test('duplicate repository URLs are rejected', function () {
    $user = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->set('newUrl', 'https://github.com/scanner/framework')
        ->call('addUrl');

    expect($user->autoScanUrls()->count())->toBe(1);
});

test('non-repository URLs are accepted for auto-scanning', function () {
    $user = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->set('newUrl', 'https://gitlab.com/group/project')
        ->call('addUrl');

    expect($user->autoScanUrls()->pluck('url'))->toContain('https://gitlab.com/group/project')
        ->and($user->autoScanUrls()->first()->status)->toBe('queued');
});

test('removing a repository URL drops it from the queue', function () {
    $user = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $url = $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);
    $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/notes']);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->call('removeUrl', $url->id);

    expect($user->autoScanUrls()->pluck('url')->all())->toBe(['https://github.com/scanner/notes']);
});

test('an auto-scan imports any queued URL as evidence with the right type', function () {
    Http::fake([
        'medium.com/*' => Http::response('<html><head><title>Building modern APIs</title><meta name="description" content="A deep dive into API design"></head><body><p>An article about building RESTful APIs with Laravel and testing them end to end.</p></body></html>', 200),
    ]);

    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $user->autoScanUrls()->create(['url' => 'https://medium.com/@dev/building-modern-apis']);

    $result = app(AutoScanService::class)->scan($user);

    expect($result['scanned'])->toBe(1)
        ->and($result['new_evidence'])->toBe(1)
        ->and($result['new_projects'])->toBe(0)
        ->and($result['error'])->toBeNull();

    $evidence = Evidence::where('user_id', $user->id)->first();

    expect($evidence)->not->toBeNull()
        ->and($evidence->type)->toBe(EvidenceType::TechnicalArticle)
        ->and($evidence->url)->toBe('https://medium.com/@dev/building-modern-apis');
});

test('an auto-scan imports queued repository URLs as evidence, projects and journal', function () {
    fakeSingleRepo();

    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    $result = app(AutoScanService::class)->scan($user);

    expect($result['scanned'])->toBe(1)
        ->and($result['new_evidence'])->toBe(1)
        ->and($result['new_projects'])->toBe(1)
        ->and($result['new_journal'])->toBe(1)
        ->and($result['error'])->toBeNull();

    expect(Evidence::where('user_id', $user->id)->count())->toBe(1)
        ->and(Project::where('user_id', $user->id)->where('status', 'published')->count())->toBe(1)
        ->and(JournalEntry::where('user_id', $user->id)->count())->toBe(1)
        ->and($user->fresh()->experience_points)->toBe(118);
});

test('an auto-scan imports new repositories as evidence, projects and journal', function () {
    fakeGithubRepos();

    $user = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $result = app(AutoScanService::class)->scan($user);

    expect($result['scanned'])->toBe(2)
        ->and($result['new_evidence'])->toBe(2)
        ->and($result['new_projects'])->toBe(2)
        ->and($result['new_journal'])->toBe(2)
        ->and($result['xp'])->toBe(236) // 2×8 evidence + 2×100 project + 2×10 journal
        ->and($result['error'])->toBeNull();

    expect(Evidence::where('user_id', $user->id)->count())->toBe(2)
        ->and(Project::where('user_id', $user->id)->where('status', 'published')->count())->toBe(2)
        ->and(JournalEntry::where('user_id', $user->id)->count())->toBe(2)
        ->and($user->fresh()->experience_points)->toBe(236)
        ->and($user->fresh()->last_auto_scan_at)->not->toBeNull();

    // A summary timeline event lands in the feed.
    $event = TimelineEvent::where('user_id', $user->id)
        ->where('type', TimelineEventType::MilestoneReached)
        ->where('data->auto_scan', true)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->title)->toContain('Auto-scan imported 2 new links');
});

test('a second auto-scan skips repositories that were already imported', function () {
    fakeGithubRepos();

    $user = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    app(AutoScanService::class)->scan($user);

    $second = app(AutoScanService::class)->scan($user);

    expect($second['new_evidence'])->toBe(0)
        ->and($second['new_projects'])->toBe(0)
        ->and($second['new_journal'])->toBe(0)
        ->and($second['xp'])->toBe(0)
        ->and(Evidence::where('user_id', $user->id)->count())->toBe(2)
        ->and(JournalEntry::where('user_id', $user->id)->count())->toBe(2);
});

test('an auto-scan without a linked github profile or saved urls reports an error', function () {
    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $result = app(AutoScanService::class)->scan($user);

    expect($result['scanned'])->toBe(0)
        ->and($result['error'])->toContain('GitHub');
});

test('the auto-scan command only scans developers with an active subscription and a source', function () {
    Http::fake([
        'api.github.com/users/scanner/repos*' => Http::response([
            [
                'name' => 'framework',
                'full_name' => 'scanner/framework',
                'description' => 'A PHP framework for fast APIs',
                'language' => 'PHP',
                'stargazers_count' => 100,
                'forks_count' => 40,
                'topics' => ['framework', 'php'],
                'homepage' => 'https://framework.dev',
                'html_url' => 'https://github.com/scanner/framework',
                'size' => 5000,
                'fork' => false,
                'archived' => false,
                'default_branch' => 'main',
                'created_at' => '2020-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-06-01T00:00:00Z',
            ],
        ], 200),
        'api.github.com/repos/scanner/*/readme' => Http::response([
            'content' => base64_encode("# Project\n\nA documented project built with PHP."),
        ], 200),
        'api.github.com/repos/scanner/framework' => Http::response([
            'name' => 'framework',
            'full_name' => 'scanner/framework',
            'description' => 'A PHP framework for fast APIs',
            'language' => 'PHP',
            'stargazers_count' => 100,
            'forks_count' => 40,
            'topics' => ['framework', 'php'],
            'homepage' => 'https://framework.dev',
            'html_url' => 'https://github.com/scanner/framework',
            'size' => 5000,
            'fork' => false,
            'archived' => false,
            'default_branch' => 'main',
            'created_at' => '2020-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'pushed_at' => '2024-06-01T00:00:00Z',
        ], 200),
    ]);

    $subscriber = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $urlOnly = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $urlOnly->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    $inactive = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->subDay(),
    ]);

    $noSource = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $this->artisan(AutoScanReposCommand::class)->assertSuccessful();

    expect(Evidence::where('user_id', $subscriber->id)->count())->toBe(1)
        ->and(Evidence::where('user_id', $urlOnly->id)->count())->toBe(1)
        ->and(Evidence::where('user_id', $inactive->id)->count())->toBe(0)
        ->and(Evidence::where('user_id', $noSource->id)->count())->toBe(0);
});

test('the active credits page shows status and runs a manual scan', function () {
    fakeGithubRepos();

    $user = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertSee('Auto-scan is active')
        ->assertSee('Scan now')
        ->call('scanNow');

    expect($component->get('scanResult')['new_evidence'])->toBe(2)
        ->and($component->get('scanResult')['xp'])->toBe(236)
        ->and(Evidence::where('user_id', $user->id)->count())->toBe(2);
});

test('the credits page shows the developer their scan history with xp awarded', function () {
    $user = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $user->autoScanRuns()->create([
        'scanned' => 3,
        'new_evidence' => 2,
        'new_projects' => 1,
        'new_journal' => 1,
        'xp' => 150,
    ]);

    $user->autoScanRuns()->create([
        'scanned' => 1,
        'new_evidence' => 1,
        'new_projects' => 0,
        'new_journal' => 0,
        'xp' => 30,
    ]);

    $user->autoScanRuns()->create([
        'scanned' => 0,
        'new_evidence' => 0,
        'new_projects' => 0,
        'new_journal' => 0,
        'xp' => 0,
        'error' => 'The queued repositories could not be fetched — check the URLs and try again.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Scan history')
        ->assertSee('+150 XP')
        ->assertSee('+30 XP')
        ->assertSee('Failed')
        ->assertSee('+180 XP total');
});

test('a manual scan records a run the developer sees in their history', function () {
    fakeGithubRepos();

    $user = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->call('scanNow')
        ->assertSet('scanResult.xp', 236)
        ->assertSee('Scan history')
        ->assertSee('+236 XP');

    $run = $user->autoScanRuns()->first();

    expect($run)->not->toBeNull()
        ->and($run->scanned)->toBe(2)
        ->and($run->new_evidence)->toBe(2)
        ->and($run->xp)->toBe(236)
        ->and($run->error)->toBeNull();
});

test('the scan history section is hidden until a scan has run', function () {
    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertDontSee('Scan history');
});

test('turning off auto-scan keeps existing work but stops scanning', function () {
    $user = User::factory()->create([
        'github_url' => 'https://github.com/scanner',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->call('cancelAutoScan');

    $user = $user->fresh();

    expect($user->auto_scan_enabled)->toBeFalse()
        ->and($user->autoScanActive())->toBeFalse();
});

test('scanning marks queued URLs as scanned with a timestamp', function () {
    fakeSingleRepo();

    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $row = $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    app(AutoScanService::class)->scan($user);

    $row = $row->fresh();

    expect($row->status)->toBe('scanned')
        ->and($row->last_scanned_at)->not->toBeNull()
        ->and($row->last_error)->toBeNull();
});

test('already-scanned URLs are skipped on the next scan', function () {
    fakeSingleRepo();

    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $user->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/framework',
        'status' => 'scanned',
        'last_scanned_at' => now(),
    ]);

    $result = app(AutoScanService::class)->scan($user);

    expect($result['scanned'])->toBe(0)
        ->and($result['new_evidence'])->toBe(0)
        ->and($result['error'])->not->toBeNull()
        ->and(Evidence::where('user_id', $user->id)->count())->toBe(0);
});

test('a URL that cannot be fetched is marked failed', function () {
    Http::fake([
        'api.github.com/repos/scanner/private-repo' => Http::response([], 404),
    ]);

    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $row = $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/private-repo']);

    $result = app(AutoScanService::class)->scan($user);

    $row = $row->fresh();

    expect($result['scanned'])->toBe(0)
        ->and($row->status)->toBe('failed')
        ->and($row->last_error)->not->toBeNull()
        ->and($result['error'])->toContain('could not be fetched');
});

test('failed URLs are retried on the next scan', function () {
    Http::fake([
        'api.github.com/repos/scanner/framework' => Http::response([
            'name' => 'framework',
            'full_name' => 'scanner/framework',
            'description' => 'A PHP framework for fast APIs',
            'language' => 'PHP',
            'stargazers_count' => 100,
            'forks_count' => 40,
            'topics' => ['framework', 'php'],
            'homepage' => 'https://framework.dev',
            'html_url' => 'https://github.com/scanner/framework',
            'size' => 5000,
            'fork' => false,
            'archived' => false,
            'default_branch' => 'main',
            'created_at' => '2020-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'pushed_at' => '2024-06-01T00:00:00Z',
        ], 200),
        'api.github.com/repos/scanner/framework/readme' => Http::response([
            'content' => base64_encode("# Framework\n\nA documented project built with PHP."),
        ], 200),
    ]);

    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $row = $user->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/framework',
        'status' => 'failed',
        'last_error' => 'Repository not found or not publicly readable.',
        'last_scanned_at' => now()->subDay(),
    ]);

    app(AutoScanService::class)->scan($user);

    $row = $row->fresh();

    expect($row->status)->toBe('scanned')
        ->and($row->last_error)->toBeNull()
        ->and(Evidence::where('user_id', $user->id)->count())->toBe(1);
});

test('the credits page shows per-URL status badges', function () {
    $user = User::factory()->create([
        'github_url' => null,
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $user->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);
    $user->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/notes',
        'status' => 'scanned',
        'last_scanned_at' => now(),
    ]);
    $user->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/private-repo',
        'status' => 'failed',
        'last_error' => 'Repository not found or not publicly readable.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Queued')
        ->assertSee('Scanned')
        ->assertSee('Failed');
});
