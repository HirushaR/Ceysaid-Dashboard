<?php

namespace App\Livewire\Admin\Finance;

use App\Services\FinanceCashFlowService;
use Livewire\Component;

class Overview extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
    }

    public function render(FinanceCashFlowService $cashFlow)
    {
        $asOf = today();

        return view('livewire.admin.finance.overview', [
            'asOf' => $asOf,
            'forecast' => $cashFlow->forecast($asOf),
            'overdue' => $cashFlow->overdue($asOf),
            'receivables' => $cashFlow->nextReceivables($asOf),
            'payables' => $cashFlow->nextPayables($asOf),
        ])->layout('components.layouts.admin', ['title' => 'Finance Overview']);
    }
}
