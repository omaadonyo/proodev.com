<?php

namespace App\Jobs;

use App\Models\JournalEntry;
use App\Services\Ai\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpandJournalWithAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public JournalEntry $entry) {}

    public function handle(AiService $ai): void
    {
        if ($this->entry->ai_processed) {
            return;
        }

        $structured = $ai->expandJournal($this->entry->content);

        if ($structured !== []) {
            $this->entry->update([
                'structured_content' => $structured,
                'ai_processed' => true,
            ]);
        }
    }
}
