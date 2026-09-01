<?php

namespace App\Listeners;

use App\Events\EvidenceAdded;
use App\Events\EvidenceAnalyzed;
use App\Mail\EvidenceActivityMail;
use App\Models\Evidence;
use App\Services\ScanEmailBatcher;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the owner whenever evidence is added or a scan completes,
 * honoring their scans & evidence email preference.
 *
 * Evidence that comes from a scan (onboarding, feed scout, auto-scan or the
 * background import queue) is never emailed individually — it is grouped by
 * the ScanEmailBatcher into one start email and one summary email.
 */
class NotifyEvidenceActivity
{
    public function handle(EvidenceAdded|EvidenceAnalyzed $event): void
    {
        $evidence = $event->evidence;
        $analyzed = $event instanceof EvidenceAnalyzed;
        $owner = $evidence->user;

        if (! $owner?->email || ! $owner->wantsEmail('scans_evidence')) {
            return;
        }

        $batcher = app(ScanEmailBatcher::class);

        // Imported evidence (metadata.imported is set by every scan path)
        // belongs to a batch: record it while the batch is open, and skip
        // individual emails for late async analysis results.
        if ($batcher->isActiveFor($owner->id) || (bool) ($evidence->metadata['imported'] ?? false)) {
            $batcher->record($evidence, $analyzed);

            return;
        }

        Mail::to($owner)->send(new EvidenceActivityMail($evidence, $analyzed));
    }
}
