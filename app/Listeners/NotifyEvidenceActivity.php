<?php

namespace App\Listeners;

use App\Events\EvidenceAdded;
use App\Events\EvidenceAnalyzed;
use App\Mail\EvidenceActivityMail;
use App\Models\Evidence;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the owner whenever evidence is added or a scan completes,
 * honoring their scans & evidence email preference.
 */
class NotifyEvidenceActivity
{
    public function handle(EvidenceAdded|EvidenceAnalyzed $event): void
    {
        $this->send($event->evidence, $event instanceof EvidenceAnalyzed);
    }

    private function send(Evidence $evidence, bool $analyzed): void
    {
        $owner = $evidence->user;

        if (! $owner?->email || ! $owner->wantsEmail('scans_evidence')) {
            return;
        }

        Mail::to($owner)->send(new EvidenceActivityMail($evidence, $analyzed));
    }
}
