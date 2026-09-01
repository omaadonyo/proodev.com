<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['mail.default' => 'log', 'queue.default' => 'sync', 'broadcasting.default' => 'log']);

$user = \App\Models\User::query()->where('is_admin', false)->first();
auth()->login($user);

$batcher = app(\App\Services\ScanEmailBatcher::class);
app()->instance(\App\Services\ScanEmailBatcher::class, $batcher);
$batcher->begin($user, 'Scout');

// Resolve the listener EXACTLY like Illuminate\Events\Dispatcher does
$closure = fn () => null;
$ref = new ReflectionMethod(app('events'), 'makeListener');
$ref->setAccessible(true);
$made = $ref->invoke(app('events'), \App\Listeners\NotifyEvidenceActivity::class);

$evidence = \App\Models\Evidence::create([
    'user_id' => $user->id,
    'type' => \App\Enums\EvidenceType::GithubRepository,
    'title' => 'probe-repo',
    'url' => 'https://github.com/t/probe-'.uniqid(),
    'source' => 'github',
    'status' => \App\Enums\EvidenceStatus::Pending,
    'metadata' => ['imported' => true],
]);

try {
    $made(new \App\Events\EvidenceAdded($evidence), []);
} catch (\Throwable $e) {
    echo 'closure threw: '.get_class($e).': '.$e->getMessage()."\n";
}

$prop = (new ReflectionClass($batcher))->getProperty('batches');
$batches = $prop->getValue($batcher);
echo 'items: '.count($batches[$user->id]['items'] ?? [])."\n";
echo 'instance hash of resolved-in-closure vs mine: unknown; direct compare next run'."\n";
