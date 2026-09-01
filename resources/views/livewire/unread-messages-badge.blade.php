<?php

use Livewire\Attributes\Computed;
use Livewire\Component;
use Wirechat\Wirechat\Helpers\MorphClassResolver;

new class extends Component
{
    public function getListeners(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $channel = 'chats.participant.'.MorphClassResolver::encode($user->getMorphClass()).'.'.$user->getKey();

        return [
            "echo-private:{$channel},.Wirechat\\Wirechat\\Events\\NotifyParticipant" => 'refreshUnread',
            "echo-private:{$channel},.Wirechat\\Wirechat\\Events\\MessageRequestUpdated" => 'refreshUnread',
        ];
    }

    public function refreshUnread(): void
    {
        unset($this->unread);
    }

    #[Computed]
    public function unread(): int
    {
        return auth()->user()->unreadMessageCount();
    }
};
?>

<div class="inline-flex shrink-0 items-center">
    @if ($this->unread > 0)
        <span
            class="ms-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
            title="{{ $this->unread }} unread message{{ $this->unread === 1 ? '' : 's' }}"
        >
            {{ min(99, $this->unread) }}
        </span>
    @endif
</div>
