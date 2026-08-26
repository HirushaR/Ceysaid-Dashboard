<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $status = '';
    #[Url] public string $queue = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function render()
    {
        $user = auth()->user();
        $query = Lead::query()->excludingOtherLeads()->with(['assignedUser', 'assignedOperator', 'creator']);
        if ($user->isSales() && ! $user->isManager()) {
            $query->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhereNull('assigned_to'));
        } elseif ($user->isOperation() && ! $user->isManager()) {
            $query->where(fn ($q) => $q->where('assigned_operator', $user->id)->orWhere('status', LeadStatus::INFO_GATHER_COMPLETE->value));
        }
        $query->when($this->search, fn ($q) => $q->where(fn ($inner) => $inner
            ->where('reference_id', 'like', "%{$this->search}%")
            ->orWhere('customer_name', 'like', "%{$this->search}%")
            ->orWhere('contact_value', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->queue === 'operations', fn ($q) => $q->where('status', LeadStatus::INFO_GATHER_COMPLETE->value));

        return view('livewire.admin.leads.index', ['leads' => $query->latest()->paginate(20), 'statuses' => LeadStatus::cases()])
            ->layout('components.layouts.admin', ['title' => 'Leads']);
    }
}
