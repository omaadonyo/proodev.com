<?php

namespace App\Livewire\Forms;

use App\Data\JournalData;
use App\Enums\Visibility;
use App\Models\JournalEntry;
use Livewire\Attributes\Rule;
use Livewire\Form;

class JournalForm extends Form
{
    public ?int $entryId = null;

    #[Rule(['nullable', 'string', 'max:160'])]
    public string $title = '';

    #[Rule(['required', 'string', 'min:10', 'max:10000'])]
    public string $content = '';

    #[Rule(['required', 'in:private,team,public'])]
    public string $visibility = 'private';

    public function set(JournalEntry $entry): void
    {
        $this->entryId = $entry->id;
        $this->title = $entry->title ?? '';
        $this->content = $entry->content;
        $this->visibility = $entry->visibility->value;
    }

    public function data(): JournalData
    {
        return JournalData::fromArray([
            'title' => $this->title,
            'content' => $this->content,
            'visibility' => Visibility::tryFrom($this->visibility) ?? Visibility::Private,
        ]);
    }
}
