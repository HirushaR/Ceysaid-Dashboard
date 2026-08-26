<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Services\LeadWorkflowService;
use Livewire\Component;

class Show extends Component
{
    public Lead $lead;
    public string $note = '';

    public function mount(): void
    {
        $this->lead->load(['assignedUser', 'assignedOperator', 'creator', 'actionLogs.user', 'notes.user', 'quote', 'invoices']);
    }

    public function transition(string $status, LeadWorkflowService $workflow): void
    {
        $to = LeadStatus::from($status);
        $this->lead = $workflow->transition($this->lead, $to, auth()->user());
        $this->lead->load(['assignedUser', 'assignedOperator', 'creator', 'actionLogs.user', 'notes.user', 'quote', 'invoices']);
        session()->flash('success', 'Lead moved to '.$to->label().'.');
    }

    public function addNote(): void
    {
        $this->validate(['note' => ['required', 'string', 'max:5000']]);
        LeadNote::create(['lead_id' => $this->lead->id, 'user_id' => auth()->id(), 'note' => $this->note]);
        $this->reset('note');
        $this->lead->load('notes.user');
    }

    public function render()
    {
        $current = LeadStatus::tryFrom($this->lead->status);
        $next = collect(LeadStatus::cases())->filter(fn ($status) => $current && app(LeadWorkflowService::class)->canTransition($current, $status, auth()->user()));
        return view('livewire.admin.leads.show', compact('next'))->layout('components.layouts.admin', ['title' => $this->lead->reference_id ?: 'Lead']);
    }
}
