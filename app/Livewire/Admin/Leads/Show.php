<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Enums\OtherLeadStatus;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Notifications\LeadDatabaseNotification;
use App\Services\LeadWorkflowService;
use App\Support\AdminNotificationAction;
use App\Support\AdminNotificationMessage;
use Livewire\Component;

class Show extends Component
{
    public Lead $lead;

    public string $note = '';

    public function mount(): void
    {
        $this->authorizeLead();
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
        abort_if($this->restrictedOperationPreview(), 403);
        $this->validate(['note' => ['required', 'string', 'max:5000']]);
        $note = LeadNote::create(['lead_id' => $this->lead->id, 'user_id' => auth()->id(), 'note' => $this->note]);
        $this->notifyInternalNoteRecipients($note);
        $this->reset('note');
        $this->lead->load('notes.user');
        session()->flash('success', 'Internal note added and the assigned team was notified.');
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
            'assignedUser', 'assignedOperator', 'visaAssignee', 'creator', 'tourMaster', 'actionLogs.user',
            'notes.user', 'quote.lineItems', 'invoices.customerPayments',
            'invoices.vendorBills.airTicketRequest.issuedBy', 'attachments',
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

        $restrictedOperationPreview = $this->restrictedOperationPreview();

        return view('livewire.admin.leads.show', compact('next', 'pipeline', 'progress', 'restrictedOperationPreview'))
            ->layout('components.layouts.admin', ['title' => $this->lead->reference_id ?: 'Lead']);
    }

    private function authorizeLead(): void
    {
        $user = auth()->user();

        if ($this->lead->is_other_lead) {
            abort_unless($user->isAdmin() || ($user->isSales() && $this->lead->created_by === $user->id), 403);

            return;
        }

        $allowed = $user->isAdmin()
            || $user->isAccount()
            || ($user->isSales() && ($user->isManager() || $this->lead->assigned_to === $user->id || $this->lead->created_by === $user->id || ($this->lead->assigned_to === null && $this->lead->status === LeadStatus::NEW->value)))
            || ($user->isOperation() && ($user->isManager() || $this->lead->assigned_operator === $user->id || ($this->lead->assigned_operator === null && $this->lead->status === LeadStatus::INFO_GATHER_COMPLETE->value)))
            || (($user->isCallCenter() || $user->isMarketing()) && $this->lead->created_by === $user->id);

        abort_unless($allowed, 403);
    }

    private function restrictedOperationPreview(): bool
    {
        $user = auth()->user();

        return $user->isOperation()
            && ! $user->isManager()
            && $this->lead->assigned_operator === null
            && $this->lead->status === LeadStatus::INFO_GATHER_COMPLETE->value;
    }

    private function notifyInternalNoteRecipients(LeadNote $note): void
    {
        $actor = auth()->user();
        $message = AdminNotificationMessage::make()
            ->title('New internal lead note')
            ->body($actor->name.' added an internal note to '.($this->lead->reference_id ?: 'lead #'.$this->lead->id).': '.str($note->note)->limit(120))
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->actions([
                AdminNotificationAction::make('view')->label('View lead')->url(route('admin.leads.show', $this->lead)),
            ]);

        collect([$this->lead->assignedUser, $this->lead->assignedOperator, $this->lead->creator])
            ->filter()
            ->unique('id')
            ->reject(fn ($recipient) => $recipient->id === $actor->id)
            ->each(fn ($recipient) => $recipient->notify(new LeadDatabaseNotification($message, $this->lead->id)));
    }
}
