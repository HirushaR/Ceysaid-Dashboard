<?php

namespace App\Livewire\Admin\Visa;

use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadActionLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $assignee = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canViewVisaQueue(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedAssignee(): void
    {
        $this->resetPage();
    }

    public function assignVisa(int $leadId, string $assigneeId): void
    {
        $actor = auth()->user();
        abort_unless($actor->canAssignVisaLeads(), 403);

        $lead = $this->eligibleLeads()->findOrFail($leadId);
        $assignee = $assigneeId === '' ? null : $this->visaTeam()->firstWhere('id', (int) $assigneeId);
        abort_if($assigneeId !== '' && ! $assignee, 422, 'Select an operation team member with visa access.');

        $lead->update(['visa_assigned_to' => $assignee?->id]);

        session()->flash('success', 'Visa lead assignment updated.');
    }

    public function updateVisaStatus(int $leadId, string $status): void
    {
        $lead = $this->eligibleLeads()->findOrFail($leadId);
        abort_unless(auth()->user()->canProcessVisaLead($lead), 403);

        validator(['status' => $status], [
            'status' => ['required', Rule::enum(ServiceStatus::class)],
        ])->validate();

        $previous = $lead->visa_status;
        $lead->update(['visa_status' => $status]);
        LeadActionLogger::log(
            $lead,
            'visa_status_changed',
            'Visa status changed from '.ServiceStatus::tryFrom($previous)?->getLabel().' to '.ServiceStatus::from($status)->getLabel().'.',
            ['visa_status' => $previous],
            ['visa_status' => $status],
        );

        session()->flash('success', 'Visa status updated.');
    }

    private function eligibleLeads(): Builder
    {
        return Lead::query()
            ->excludingOtherLeads()
            ->notArchived()
            ->whereIn('status', [
                LeadStatus::CONFIRMED->value,
                LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value,
            ]);
    }

    private function queueQuery(): Builder
    {
        $query = $this->visibleLeads()->with(['assignedUser', 'assignedOperator', 'visaAssignee']);

        return $query
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('reference_id', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%")
                ->orWhere('destination', 'like', "%{$this->search}%")
                ->orWhere('contact_value', 'like', "%{$this->search}%")))
            ->when($this->status, fn (Builder $query) => $query->where('visa_status', $this->status))
            ->when($this->assignee === 'unassigned', fn (Builder $query) => $query->whereNull('visa_assigned_to'))
            ->when(ctype_digit($this->assignee), fn (Builder $query) => $query->where('visa_assigned_to', (int) $this->assignee));
    }

    private function visibleLeads(): Builder
    {
        $query = $this->eligibleLeads();
        $user = auth()->user();
        if (! $user->canViewAllVisaLeads()) {
            $query->where('visa_assigned_to', $user->id);
        }

        return $query;
    }

    /** @return Collection<int, User> */
    private function visaTeam(): Collection
    {
        return User::query()
            ->where('role', 'operation')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->canViewVisaQueue())
            ->values();
    }

    public function render()
    {
        $base = $this->visibleLeads();
        $summary = [
            'total' => (clone $base)->count(),
            'unassigned' => (clone $base)->whereNull('visa_assigned_to')->count(),
            'in_progress' => (clone $base)->where('visa_status', ServiceStatus::IN_PROGRESS->value)->count(),
            'done' => (clone $base)->where('visa_status', ServiceStatus::DONE->value)->count(),
        ];

        return view('livewire.admin.visa.index', [
            'leads' => $this->queueQuery()->latest('updated_at')->paginate(20),
            'summary' => $summary,
            'visaTeam' => $this->visaTeam(),
            'serviceStatuses' => ServiceStatus::options(),
            'canAssign' => auth()->user()->canAssignVisaLeads(),
        ])->layout('components.layouts.admin', ['title' => 'Visa Processing']);
    }
}
