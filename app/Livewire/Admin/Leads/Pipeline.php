<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

class Pipeline extends Component
{
    #[Url] public string $search = '';
    #[Url] public string $type = '';

    private function visibleQuery(): Builder
    {
        $user = auth()->user();
        $query = Lead::query()->excludingOtherLeads()->notArchived();

        if ($user->isSales() && ! $user->isManager()) {
            $query->where(fn (Builder $q) => $q->where('assigned_to', $user->id)->orWhereNull('assigned_to'));
        } elseif ($user->isOperation() && ! $user->isManager()) {
            $query->where(fn (Builder $q) => $q->where('assigned_operator', $user->id)
                ->orWhere('status', LeadStatus::INFO_GATHER_COMPLETE->value));
        }

        return $query
            ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('reference_id', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%")
                ->orWhere('destination', 'like', "%{$this->search}%")))
            ->when($this->type === 'group', fn (Builder $q) => $q->where('is_group_lead', true))
            ->when($this->type === 'cruise', fn (Builder $q) => $q->where('is_cruise_lead', true))
            ->when($this->type === 'standard', fn (Builder $q) => $q->where('is_group_lead', false)->where('is_cruise_lead', false));
    }

    public function render()
    {
        $definitions = [
            ['label' => 'New', 'statuses' => [LeadStatus::NEW]],
            ['label' => 'Assigned to Sales', 'statuses' => [LeadStatus::ASSIGNED_TO_SALES]],
            ['label' => 'Info Gather Complete', 'statuses' => [LeadStatus::INFO_GATHER_COMPLETE]],
            ['label' => 'Assigned to Operations', 'statuses' => [LeadStatus::ASSIGNED_TO_OPERATIONS]],
            ['label' => 'Pricing & Amendments', 'statuses' => [LeadStatus::RATE_REQUESTED, LeadStatus::AMENDMENT]],
            ['label' => 'Operation Complete', 'statuses' => [LeadStatus::OPERATION_COMPLETE]],
            ['label' => 'Sent to Customer', 'statuses' => [LeadStatus::SENT_TO_CUSTOMER]],
            ['label' => 'Confirmed', 'statuses' => [LeadStatus::CONFIRMED]],
        ];

        $columns = collect($definitions)->map(function (array $definition): array {
            $statuses = collect($definition['statuses'])->map->value->all();
            $query = $this->visibleQuery()->whereIn('status', $statuses);

            return $definition + [
                'count' => (clone $query)->count(),
                'leads' => $query->with(['assignedUser', 'assignedOperator'])->latest('updated_at')->limit(40)->get(),
            ];
        });

        return view('livewire.admin.leads.pipeline', compact('columns'))
            ->layout('components.layouts.admin', ['title' => 'Sales Pipeline']);
    }
}
