<?php

namespace App\Services;

use App\Mail\ScanCompletedMail;
use App\Mail\ScanStartedMail;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Groups per-item scan notifications into exactly two emails per run: the
 * first shows what is being scanned with what is queued, the last carries
 * details on every scanned project. Used by onboarding, the feed scout,
 * auto-scans and the background import queue.
 */
class ScanEmailBatcher
{
    /** @var array<int, array<string, mixed>> */
    protected array $batches = [];

    /**
     * Open a batch for a user. Nothing is emailed until announce() fires.
     */
    public function begin(User $user, string $context): void
    {
        if (getenv('FR_DEBUG')) {
            file_put_contents(
                storage_path('logs/fr-debug.log'),
                'begin hash='.spl_object_hash($this).' uid='.$user->id."\n",
                FILE_APPEND,
            );
        }

        $this->batches[$user->id] = [
            'user' => $user,
            'context' => $context,
            'first_sent' => false,
            'items' => [],
        ];
    }

    /**
     * Send the first email: what is scanned with what is queued.
     *
     * @param  array<int, string>  $scanned
     * @param  array<int, string>  $queued
     */
    public function announce(int|string|User $user, string $headline, array $scanned = [], array $queued = []): void
    {
        $userId = $user instanceof User ? $user->id : (int) $user;
        $batch = $this->batches[$userId] ?? null;

        if (! $batch || $batch['first_sent']) {
            return;
        }

        $this->batches[$userId]['first_sent'] = true;

        if (! $this->wantsEmail($batch['user'])) {
            return;
        }

        Mail::to($batch['user'])->send(
            new ScanStartedMail($batch['user'], $batch['context'], $headline, $scanned, $queued),
        );
    }

    /**
     * Capture an evidence item for the summary instead of emailing it alone.
     * Safe to call after the batch closed — late analysis updates are simply
     * dropped so nobody gets a follow-up email per item.
     */
    public function record(Evidence $evidence, bool $analyzed = false): void
    {
        if (getenv('FR_DEBUG')) {
            file_put_contents(
                storage_path('logs/fr-debug.log'),
                'record hash='.spl_object_hash($this).' uid='.$evidence->user_id.' analyzed='.var_export($analyzed, true)."\n",
                FILE_APPEND,
            );
        }

        if ($analyzed) {
            if (isset($this->batches[$evidence->user_id]['items'][$evidence->id])) {
                $this->batches[$evidence->user_id]['items'][$evidence->id]['score'] = $evidence->ai_score;
            }

            return;
        }

        $this->batches[$evidence->user_id]['items'][$evidence->id] = [
            'title' => $evidence->title,
            'url' => $evidence->url,
            'type' => $evidence->type->label(),
            'score' => null,
        ];
    }

    /**
     * Close the batch and send the last email with details on every scanned
     * project. Sends nothing when no items were recorded.
     */
    public function complete(int|string|User $user): void
    {
        $userId = $user instanceof User ? $user->id : (int) $user;
        $batch = $this->batches[$userId] ?? null;

        if (getenv('FR_DEBUG')) {
            file_put_contents(
                storage_path('logs/fr-debug.log'),
                'complete hash='.spl_object_hash($this).' uid='.$userId.' hasBatch='.var_export($batch !== null, true)
                .' items='.count($batch['items'] ?? [])."\n",
                FILE_APPEND,
            );
        }

        unset($this->batches[$userId]);

        if (! $batch || $batch['items'] === [] || ! $this->wantsEmail($batch['user'])) {
            return;
        }

        Mail::to($batch['user'])->send(
            new ScanCompletedMail($batch['user'], $batch['context'], array_values($batch['items'])),
        );
    }

    /**
     * Discard an open batch without sending the summary (failed scans).
     */
    public function abandon(int|string|User $user): void
    {
        unset($this->batches[$user instanceof User ? $user->id : (int) $user]);
    }

    public function isActiveFor(int $userId): bool
    {
        return isset($this->batches[$userId]);
    }

    private function wantsEmail(User $user): bool
    {
        return (bool) $user->email && $user->wantsEmail('scans_evidence');
    }
}
