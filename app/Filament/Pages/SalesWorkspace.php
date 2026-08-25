<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\PilotSalesPage;
use App\Services\SalesWorkspaceQuery;
use Filament\Pages\Page;
use Livewire\WithPagination;

class SalesWorkspace extends Page
{
    use PilotSalesPage, WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Sales Workspace';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Sales Workspace';

    protected static string $view = 'filament.pages.sales-workspace';

    public string $search = '';

    public string $stage = '';

    private const SALES_STAGES = [
        'new_inquiry', 'assigned', 'qualification', 'ready_for_pricing',
        'pricing', 'quote_sent', 'negotiation', 'confirmed',
    ];

    public function getLeads()
    {
        return app(SalesWorkspaceQuery::class)->leads(auth()->user())
            ->with(['salesOwner'])
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('reference_id', 'like', '%'.$this->search.'%')->orWhere('customer_name', 'like', '%'.$this->search.'%')->orWhere('destination', 'like', '%'.$this->search.'%')))
            ->when($this->stage, fn ($query) => $query->where('lifecycle_stage', $this->stage), fn ($query) => $query->whereIn('lifecycle_stage', self::SALES_STAGES))
            ->orderByRaw('next_action_at IS NULL')->orderBy('next_action_at')->orderByDesc('updated_at')->paginate(20);
    }

    public function getMetrics(): array
    {
        $query = app(SalesWorkspaceQuery::class)->leads(auth()->user());

        return [
            'active' => (clone $query)->whereIn('lifecycle_stage', self::SALES_STAGES)->count(),
            'unassigned' => (clone $query)->whereIn('lifecycle_stage', self::SALES_STAGES)->whereNull('sales_owner_id')->count(),
            'due_today' => (clone $query)->whereIn('lifecycle_stage', self::SALES_STAGES)->whereDate('next_action_at', today())->count(),
            'overdue' => (clone $query)->whereIn('lifecycle_stage', self::SALES_STAGES)->where('next_action_at', '<', now())->count(),
        ];
    }
}
