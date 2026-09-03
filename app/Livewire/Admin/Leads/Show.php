<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Enums\OtherLeadStatus;
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
        $this->loadLead();
    }

    public function transition(string $status, LeadWorkflowService $workflow): void
    {
        $to = LeadStatus::from($status);
        $this->lead = $workflow->transition($this->lead, $to, auth()->user());
        $this->loadLead();
        session()->flash('success', 'Lead moved to '.$to->label().'.');
    }

    public function addNote(): void
    {
        $this->validate(['note' => ['required', 'string', 'max:5000']]);
        LeadNote::create(['lead_id' => $this->lead->id, 'user_id' => auth()->id(), 'note' => $this->note]);
        $this->reset('note');
        $this->lead->load('notes.user');
    }

    public function transitionOther(string $status): void
    {
        abort_unless($this->lead->is_other_lead && auth()->user()->isSales() && $this->lead->created_by === auth()->id(), 403);
        $to = OtherLeadStatus::from($status);
        $from = $this->lead->other_lead_status;
        $allowed = ($from === OtherLeadStatus::Draft && $to === OtherLeadStatus::Confirmed)
            || ($from === OtherLeadStatus::Confirmed && $to === OtherLeadStatus::Completed);
        abort_unless($allowed, 422);
        $this->lead->update(['other_lead_status' => $to]);
        $this->loadLead();
        session()->flash('success', 'Other lead moved to '.$to->label().'.');
    }

    private function loadLead(): void
    {
        $this->lead->load([
            'assignedUser', 'assignedOperator', 'creator', 'actionLogs.user',
            'notes.user', 'quote.lineItems', 'invoices.customerPayments', 'attachments',
        ]);
    }

    public function render()
    {
        $current = LeadStatus::tryFrom($this->lead->status);
        $next = collect(LeadStatus::cases())->filter(fn ($status) => $current && app(LeadWorkflowService::class)->canTransition($current, $status, auth()->user()));
        $pipeline = collect([
            LeadStatus::NEW, LeadStatus::ASSIGNED_TO_SALES, LeadStatus::INFO_GATHER_COMPLETE,
            LeadStatus::ASSIGNED_TO_OPERATIONS, LeadStatus::OPERATION_COMPLETE,
            LeadStatus::SENT_TO_CUSTOMER, LeadStatus::CONFIRMED, LeadStatus::DOCUMENT_UPLOAD_COMPLETE,
        ]);
        $progressStatus = in_array($current, [LeadStatus::RATE_REQUESTED, LeadStatus::AMENDMENT], true)
            ? LeadStatus::ASSIGNED_TO_OPERATIONS : $current;
        $position = $pipeline->search($progressStatus);
        $progress = $position === false ? 0 : (int) $position;

        return view('livewire.admin.leads.show', compact('next', 'pipeline', 'progress'))
            ->layout('components.layouts.admin', ['title' => $this->lead->reference_id ?: 'Lead']);
    }
}
