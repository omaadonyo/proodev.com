<?php

namespace App\Actions\Journal;

use App\Data\JournalData;
use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Jobs\ExpandJournalWithAi;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\TimelineService;

class SaveJournalEntryAction
{
    public function __construct(private TimelineService $timeline) {}

    public function handle(User $user, JournalData $data, ?JournalEntry $entry = null): JournalEntry
    {
        $entry ??= new JournalEntry(['user_id' => $user->id]);

        $wasPublic = $entry->isPublic();

        $entry->fill($data->persist());

        if ($data->visibility === Visibility::Public && ! $wasPublic) {
            $entry->published_at = $entry->published_at ?? now();
        }

        $entry->save();

        if (! $entry->ai_processed) {
            dispatch(new ExpandJournalWithAi($entry));
        }

        if ($entry->isPublic() && ! $wasPublic) {
            $this->timeline->record(
                $user,
                TimelineEventType::JournalPublished,
                'Published journal entry: '.($entry->title ?: 'Untitled'),
                null,
                ['journal_id' => $entry->id],
                $entry,
            );
        }

        return $entry;
    }
}
