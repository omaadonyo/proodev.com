<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $result = app(App\Services\SystemResetService::class)->reset();
    echo "RESET OK\n";
    print_r($result);
} catch (\Throwable $e) {
    echo 'RESET FAILED: '.get_class($e)."\n";
    echo $e->getMessage()."\n";
    echo "\n-- Trace (first 8 frames) --\n";
    foreach (array_slice($e->getTrace(), 0, 8) as $i => $frame) {
        echo '#'.$i.' '.($frame['file'] ?? '?').':'.($frame['line'] ?? '?').' '.($frame['function'] ?? '')."\n";
    }
}
