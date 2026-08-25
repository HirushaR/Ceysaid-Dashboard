<?php

namespace App\Filament\Pages;

use App\Enums\WorkflowTaskStatus;
use App\Filament\Pages\Concerns\PilotSalesPage;
use App\Models\WorkflowTask;
use Filament\Pages\Page;

class MyWork extends Page
{
    use PilotSalesPage;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'My Work';

    protected static ?string $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.my-work';

    public function getTasks()
    {
        return WorkflowTask::with('lead')->where('owner_id', auth()->id())
            ->whereIn('status', [WorkflowTaskStatus::Open, WorkflowTaskStatus::InProgress, WorkflowTaskStatus::Waiting])
            ->orderByRaw('due_at IS NULL')->orderBy('due_at')->limit(100)->get();
    }
}
