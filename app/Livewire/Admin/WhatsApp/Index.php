<?php

namespace App\Livewire\Admin\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $scope = '';

    #[Url]
    public ?int $folder = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isSales(), 403);

        if ($this->scope === '') {
            $this->scope = 'unassigned';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedScope(): void
    {
        $this->resetPage();
    }

    public function updatedFolder(): void
    {
        $this->resetPage();
    }

    public function assignConversation(int $conversationId, string $assigneeId, WhatsAppAssignmentService $service): void
    {
        $actor = auth()->user();
        abort_unless($actor->canAssignWhatsAppConversations(), 403);

        $conversation = WhatsAppConversation::findOrFail($conversationId);
        $assignee = $assigneeId === '' ? null : $this->assignmentTeam()->firstWhere('id', (int) $assigneeId);
        abort_if($assigneeId !== '' && ! $assignee, 422, 'Select a sales team member.');

        $service->assign($conversation, $actor, $assignee);
        session()->flash('success', $assignee ? "Conversation assigned to {$assignee->name}." : 'Conversation returned to the unassigned queue.');
    }

    public function claimConversation(int $conversationId, WhatsAppAssignmentService $service): void
    {
        $actor = auth()->user();
        abort_unless($actor->canClaimWhatsAppConversations(), 403);

        $conversation = WhatsAppConversation::query()->whereNull('assigned_to')->findOrFail($conversationId);
        $service->claim($conversation, $actor);
        session()->flash('success', 'Conversation assigned to you.');
    }

    /** @return Collection<int, User> */
    private function assignmentTeam(): Collection
    {
        return User::query()->where('role', 'sales')->orderBy('name')->get();
    }

    public function render()
    {
        $user = auth()->user();
        $canAssign = $user->canAssignWhatsAppConversations();
        $allowedScopes = $canAssign ? ['mine', 'unassigned', 'all'] : ['mine', 'unassigned'];
        $scope = in_array($this->scope, $allowedScopes, true) ? $this->scope : 'mine';

        $query = WhatsAppConversation::query()
            ->with(['contact', 'lead', 'assignedUser', 'folder'])
            ->when($scope === 'mine', fn (Builder $query) => $query->where('assigned_to', $user->id))
            ->when($scope === 'unassigned', fn (Builder $query) => $query->whereNull('assigned_to'))
            ->when($this->folder, fn (Builder $query) => $query->where('folder_id', $this->folder))
            ->when($this->search, fn (Builder $query) => $query->whereHas('contact', fn (Builder $contact) => $contact
                ->where('profile_name', 'like', '%'.$this->search.'%')
                ->orWhere('phone', 'like', '%'.$this->search.'%')));

        return view('livewire.admin.whatsapp.index', [
            'conversations' => $query->orderByRecentActivity()->paginate(30),
            'folders' => $user->whatsappChatFolders()->orderBy('position')->get(),
            'assignmentTeam' => $canAssign ? $this->assignmentTeam() : collect(),
            'canAssign' => $canAssign,
            'canClaim' => $user->canClaimWhatsAppConversations(),
            'unassignedCount' => WhatsAppConversation::unassigned()->count(),
        ])->layout('components.layouts.admin', ['title' => 'WhatsApp inbox']);
    }
}
