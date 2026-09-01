<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationsBell extends Component
{
    public int $userId = 0;

    public function mount(): void
    {
        $this->userId = (int) auth()->id();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function recent(): Collection
    {
        return auth()->user()->notifications()->latest()->take(5)->get();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()
            ->where('id', $id)
            ->get()
            ->each->markAsRead();

        unset($this->unreadCount, $this->recent);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->each->markAsRead();

        unset($this->unreadCount, $this->recent);
    }

    public function clearAll(): void
    {
        auth()->user()->notifications()->delete();

        unset($this->unreadCount, $this->recent);
    }

    #[On('echo-private:App.Models.User.{userId},Illuminate\\Notifications\\Events\\BroadcastNotificationCreated')]
    public function refresh(): void
    {
        unset($this->unreadCount, $this->recent);
    }

    public function render(): View
    {
        return view('livewire.notifications-bell');
    }
}
