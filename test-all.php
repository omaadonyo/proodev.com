<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

$fail = function (string $m) { echo "FAIL: $m\n"; exit(1); };

// Route all mail to a log file we can inspect
$logPath = storage_path('logs/laravel.log');
config([
    'mail.default' => 'log',
    'queue.default' => 'sync',
    'broadcasting.default' => 'log',
]);

$readLog = fn (): string => @file_get_contents($logPath) ?: '';

$user = \App\Models\User::query()->where('is_admin', false)->first();
auth()->login($user);

// ---- Batcher flow ------------------------------------------------------
$batcher = app(\App\Services\ScanEmailBatcher::class);
app()->instance(\App\Services\ScanEmailBatcher::class, $batcher); // same instance the listener resolves

$batcher->begin($user, 'Scout');
$batcher->announce($user, 'Scanning @tester repos', ['repo-a', 'repo-b'], ['5 items queued for background scanning']);

$log = $readLog();
str_contains($log, 'Scout started') || $fail('start email not sent');
str_contains($log, 'queued') || str_contains($log, '=C2=B7 1 queued') || $fail('queued info missing from start email');

$evidence = \App\Models\Evidence::create([
    'user_id' => $user->id,
    'type' => \App\Enums\EvidenceType::GithubRepository,
    'title' => 'repo-a',
    'url' => 'https://github.com/tester/repo-a-'.uniqid(),
    'source' => 'github',
    'status' => \App\Enums\EvidenceStatus::Pending,
    'metadata' => ['imported' => true],
]);
Event::dispatch(new \App\Events\EvidenceAdded($evidence));

$log = $readLog();
! str_contains($log, 'Evidence added: repo-a') || $fail('per-item email WAS sent during batch');
echo "1. Start email sent once; per-item email suppressed during batch: OK\n";

$batcher->record($evidence, false);
$batcher->complete($user);

$log = $readLog();
str_contains($log, 'Scout complete — 1 project scanned') || $fail('summary email missing');
str_contains($log, 'repo-a') || $fail('summary missing item title');
echo "2. Completion email contains scanned item details: OK\n";

// Late async analysis after batch closed → no extra email
$countBefore = substr_count($readLog(), 'Subject:');
$evidence->update(['ai_score' => 77, 'metadata' => ['imported' => true]]);
Event::dispatch(new \App\Events\EvidenceAnalyzed($evidence));
substr_count($readLog(), 'Subject:') === $countBefore || $fail('extra email after batch closed');
echo "3. Late analysis produces no extra email: OK\n";

// Manual (non-imported) evidence keeps the individual email
Event::dispatch(new \App\Events\EvidenceAdded(\App\Models\Evidence::create([
    'user_id' => $user->id,
    'type' => \App\Enums\EvidenceType::GithubRepository,
    'title' => 'manual-add',
    'url' => 'https://github.com/tester/manual-'.uniqid(),
    'source' => 'github',
    'status' => \App\Enums\EvidenceStatus::Pending,
])));
$log = $readLog();
str_contains($log, 'Evidence added: manual-add') || $fail('manual evidence did not email individually');
echo "4. Manual evidence still emails individually: OK\n";

// ---- Pages render ------------------------------------------------------
$html = app(\Livewire\LivewireManager::class)->mount('pages::feed', []);
$html = is_string($html) ? $html : $html->html();
str_contains($html, 'Feature Requests') || $fail('feature requests missing on feed');
! str_contains($html, 'from-violet-500 to-cyan-400') || $fail('gradient bar still on feed');
echo "5. Feed renders with global feature-request bubble + monochrome bars: OK\n";

$html2 = app(\Livewire\LivewireManager::class)->mount('pages::projects.create', []);
$html2 = is_string($html2) ? $html2 : $html2->html();
foreach (['Paste a GitHub profile, repo or project URL to scout it live…', 'Link your GitHub in settings'] as $needle) {
    str_contains($html2, $needle) || $fail("create page missing: $needle");
}
echo "6. Create project form matches feed scout form: OK\n";
