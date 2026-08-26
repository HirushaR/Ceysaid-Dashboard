<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardIndex extends Component
{
    use WithPagination;

    public string $mode = 'leads';
    #[Url] public string $search = '';

    public function mount(string $mode = 'leads'): void
    {
        $this->mode = $mode;
        $this->authorizeMode();
    }

    public function updatedSearch(): void { $this->resetPage(); }

    private function authorizeMode(): void
    {
        $user = auth()->user();
        $allowed = match ($this->mode) {
            'other', 'my-sales', 'cruise', 'group' => $user->isSales(),
            'confirmed', 'visa' => $user->isSales() || $user->isOperation() || $user->isAdmin(),
            'notes' => $user->isSales() || $user->isOperation(),
            default => true,
        };
        abort_unless($allowed, 403);
    }

    private function query(): Builder
    {
        $user = auth()->user();
        $query = Lead::query()->notArchived()->with(['assignedUser', 'assignedOperator', 'notes' => fn ($q) => $q->latest()]);

        match ($this->mode) {
            'other' => $query->where('is_other_lead', true)->where('created_by', $user->id),
            'my-sales' => $query->excludingOtherLeads()->where('assigned_to', $user->id),
            'cruise' => $query->excludingOtherLeads()->where('assigned_to', $user->id)->where('is_cruise_lead', true),
            'group' => $query->excludingOtherLeads()->where('assigned_to', $user->id)->where('is_group_lead', true),
            'confirmed', 'visa' => $this->confirmedQuery($query, $user),
            'notes' => $query->excludingOtherLeads()->where(fn (Builder $q) => $q->where('assigned_to', $user->id)->orWhere('assigned_operator', $user->id))->whereHas('notes'),
            default => $query->excludingOtherLeads(),
        };

        return $query->when($this->search, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
            ->where('reference_id', 'like', "%{$this->search}%")
            ->orWhere('customer_name', 'like', "%{$this->search}%")
            ->orWhere('destination', 'like', "%{$this->search}%")));
    }

    private function confirmedQuery(Builder $query, $user): void
    {
        $query->excludingOtherLeads()->whereIn('status', [LeadStatus::CONFIRMED->value, LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value]);
        if ($user->isSales()) {
            $query->where(fn (Builder $q) => $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id));
        } elseif ($user->isOperation()) {
            $query->where(fn (Builder $q) => $q->where('assigned_operator', $user->id)->orWhere('created_by', $user->id));
        }
    }

    public function render()
    {
        $titles = ['other'=>'Other Leads','my-sales'=>'My Sales','cruise'=>'Cruise Leads','group'=>'Group Leads','confirmed'=>'Confirmed Leads','visa'=>'Visa Leads','notes'=>'Internal Notes'];
        $title = $titles[$this->mode] ?? 'Leads';
        $leads = $this->query()->latest('updated_at')->paginate(20);

        return view('livewire.admin.leads.dashboard-index', compact('leads', 'title'))
            ->layout('components.layouts.admin', ['title' => $title]);
    }
}
