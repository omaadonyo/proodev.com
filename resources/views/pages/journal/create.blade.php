<?php

use App\Livewire\Forms\JournalForm;
use App\Models\JournalEntry;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Journal Entry')] class extends Component {
    public JournalForm $form;

    public function save(): void
    {
        $this->authorize('create', JournalEntry::class);

        $this->form->validate();

        $entry = app(\App\Actions\Journal\SaveJournalEntryAction::class)->handle(auth()->user(), $this->form->data());

        Flux::toast(variant: 'success', text: 'Entry saved'.($entry->isPublic() ? ' and shared.' : '.'));

        $this->redirectRoute('journal.show', $entry, navigate: true);
    }
}
?>

<div class="mx-auto grid max-w-2xl gap-6">
    <div>
        <flux:heading size="xl">New journal entry</flux:heading>
        <flux:text>Capture what you shipped, solved, and learned. AI will structure it into portfolio evidence.</flux:text>
    </div>

    <form wire:submit="save" class="grid gap-5">
        <flux:field>
            <flux:label>Title</flux:label>
            <flux:input wire:model="form.title" placeholder="e.g. Squashed an N+1 that was killing our dashboard" />
            <flux:error name="form.title" />
        </flux:field>

        <flux:field>
            <flux:label>Notes *</flux:label>
            <flux:textarea wire:model="form.content" rows="8" placeholder="What did you build today? What bug took the longest to solve? What did you learn?" />
            <flux:error name="form.content" />
        </flux:field>

        <flux:field>
            <flux:label>Visibility</flux:label>
            <x-searchable-select wire:model="form.visibility">
<option value="private">Private, only you</option>
                                        <option value="team">Team, visible to vouches/colleagues</option>
                                        <option value="public">Public, appears on your DevID</option>
            </x-searchable-select>
            <flux:error name="form.visibility" />
        </flux:field>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" href="{{ route('journal.index') }}" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save entry</flux:button>
        </div>
    </form>
</div>
