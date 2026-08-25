<?php

namespace App\Filament\Pages;

use App\Enums\LeadLifecycleStage;
use App\Filament\Pages\Concerns\PilotSalesPage;
use App\Services\SalesWorkspaceQuery;
use Filament\Pages\Page;

class SalesPipeline extends Page
{
    use PilotSalesPage;

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Sales Pipeline';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.sales-pipeline';

    public function getColumns(): array
    {
        $stages = array_slice(LeadLifecycleStage::cases(), 0, 7);
        $query = app(SalesWorkspaceQuery::class)->leads(auth()->user());

        return collect($stages)->mapWithKeys(fn ($stage) => [$stage->value => [
            'label' => str($stage->value)->replace('_', ' ')->title()->toString(),
            'count' => (clone $query)->where('lifecycle_stage', $stage)->count(),
            'leads' => (clone $query)->where('lifecycle_stage', $stage)->orderByDesc('updated_at')->limit(20)->get(),
        ]])->all();
    }
}
