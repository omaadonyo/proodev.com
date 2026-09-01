<?php

use App\Models\ChatReminderMute;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $conversationId = 0;

    public bool $muted = false;

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;
        $this->muted = ChatReminderMute::where('user_id', auth()->id())
            ->where('conversation_id', $conversationId)
            ->exists();
    }

    public function toggle(): void
    {
        $existing = ChatReminderMute::where('user_id', auth()->id())
            ->where('conversation_id', $this->conversationId)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->muted = false;
        } else {
            ChatReminderMute::create([
                'user_id' => auth()->id(),
                'conversation_id' => $this->conversationId,
            ]);
            $this->muted = true;
        }
    }
}
?>

<button
    type="button"
    wire:click="toggle"
    :title="$muted ? 'Email reminders are muted for this chat' : 'Email reminders are on for this chat'"
    @class([
        'inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-semibold shadow-sm transition',
        'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $muted,
        'border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300' => $muted,
    ])
>
    <flux:icon :name="$muted ? 'bell-slash' : 'bell'" variant="mini" class="size-3.5" />
    <span class="hidden sm:inline">{{ $muted ? 'Reminders off' : 'Reminders on' }}</span>
</button>
