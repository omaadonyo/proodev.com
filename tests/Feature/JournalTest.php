<?php

use App\Enums\Visibility;
use App\Models\JournalEntry;
use App\Models\User;

test('the journal show page renders entry content through safe markdown', function () {
    $user = User::factory()->create();

    $entry = JournalEntry::create([
        'user_id' => $user->id,
        'title' => 'Ship notes',
        'content' => "<p align=\"left\"><strong>Shipped</strong> the event pipeline.</p>\n\n## Decisions\n\n- used queues\n- added retries",
        'visibility' => Visibility::Public,
        'ai_processed' => false,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('journal.show', $entry))
        ->assertOk()
        ->assertSee('Shipped')
        ->assertSee('<h2>Decisions</h2>', false)
        ->assertDontSee('align=', false);
});

test('the journal show page renders the AI summary safely', function () {
    $user = User::factory()->create();

    $entry = JournalEntry::create([
        'user_id' => $user->id,
        'title' => 'Structured entry',
        'content' => 'Raw notes',
        'structured_content' => [
            'summary' => "<p align=\"left\">A **solid week** of building.</p>\n\n## Wrap-up",
            'highlights' => ['Fixed the queue bug'],
            'tags' => ['laravel'],
        ],
        'visibility' => Visibility::Public,
        'ai_processed' => true,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('journal.show', $entry))
        ->assertOk()
        ->assertSee('solid week')
        ->assertSee('<strong>solid week</strong>', false)
        ->assertSee('<h2>Wrap-up</h2>', false)
        ->assertDontSee('align=', false);
});

test('journal index cards sanitize raw markup in summaries and content', function () {
    $user = User::factory()->create();

    JournalEntry::create([
        'user_id' => $user->id,
        'title' => 'Dirty summary',
        'content' => '<p align="left"><strong>Raw</strong> body text</p>',
        'structured_content' => ['summary' => '<p align="left"><strong>Raw</strong> summary</p>'],
        'visibility' => Visibility::Public,
        'ai_processed' => true,
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('journal.index'))
        ->assertOk()
        ->assertSee('Raw summary')
        ->assertDontSee('align=', false);
});
