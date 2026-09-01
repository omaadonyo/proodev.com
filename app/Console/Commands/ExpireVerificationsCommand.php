<?php

namespace App\Console\Commands;

use App\Models\VerificationRequest;
use Illuminate\Console\Command;

class ExpireVerificationsCommand extends Command
{
    protected $signature = 'os:expire-verifications';

    protected $description = 'Expire verification requests past their expiry date';

    public function handle(): int
    {
        $expired = VerificationRequest::where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} verification(s).");

        return self::SUCCESS;
    }
}
