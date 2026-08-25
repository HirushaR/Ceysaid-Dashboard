<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\PilotSalesPage;
use App\Models\Lead;
use App\Services\SalesWorkspaceQuery;
use Filament\Pages\Page;

class LeadWorkspace extends Page
{
    use PilotSalesPage;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.lead-workspace';

    public ?Lead $lead = null;

    public function mount(): void
    {
        $id = request()->integer('lead');
        $this->lead = app(SalesWorkspaceQuery::class)->leads(auth()->user())
            ->with(['salesOwner', 'operationsOwner', 'workflowTasks', 'workflowEvents', 'quote', 'invoices', 'attachments'])
            ->findOrFail($id);
    }

    public function getTitle(): string
    {
        return $this->lead?->reference_id.' · '.$this->lead?->customer_name;
    }
}
