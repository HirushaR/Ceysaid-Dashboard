<?php

namespace App\Livewire\Admin\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    #[Url] public string $filter = 'all';

    public function markRead(string $id): void
    {
        $this->notification($id)->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(string $id): void
    {
        $this->notification($id)->delete();
    }

    private function notification(string $id): DatabaseNotification
    {
        return auth()->user()->notifications()->findOrFail($id);
    }

    public function render()
    {
        $notifications = auth()->user()->notifications()->latest()
            ->when($this->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->paginate(30);

        return view('livewire.admin.notifications.index', compact('notifications'))
            ->layout('components.layouts.admin', ['title' => 'Notifications']);
    }
}
