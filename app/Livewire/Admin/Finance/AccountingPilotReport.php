<?php

namespace App\Livewire\Admin\Finance;

use App\Services\AccountingPilot;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AccountingPilotReport extends Component
{
    public string $asOf = '';

    #[Locked]
    public string $reportDate = '';

    public string $closeThrough = '';

    public string $closeEvidence = '';

    public function mount(): void
    {
        $this->authorizePage();
        $this->asOf = $this->reportDate = AccountingPilot::today();
    }

    public function runReport(): void
    {
        $this->authorizePage();
        $data = $this->validate(['asOf' => 'required|date_format:Y-m-d|before_or_equal:'.AccountingPilot::today()]);
        $this->reportDate = $data['asOf'];
    }

    public function close(AccountingPilot $pilot): void
    {
        $pilot->close(auth()->user(), $this->closeThrough, $this->closeEvidence);
        $this->reset('closeThrough', 'closeEvidence');
        session()->flash('success', 'Pilot period closed.');
    }

    public function render()
    {
        $this->authorizePage();
        $pilot = app(AccountingPilot::class);

        return view('livewire.admin.finance.accounting-pilot-report', [
            'balance' => $pilot->trialBalance($this->reportDate),
            'customerBalances' => $pilot->customerBalances($this->reportDate),
            'supplierAccruals' => $pilot->supplierAccruals($this->reportDate),
            'closedThrough' => $pilot->state()->closed_through,
            'outstandingWork' => $pilot->outstandingWork($this->reportDate),
            'pending' => DB::table('accounting_journals')->whereNull('posted_at')->whereNull('rejected_at')
                ->join('invoices', 'invoices.id', '=', 'accounting_journals.invoice_id')
                ->orderBy('posting_date')->get(['invoice_id', 'invoice_number', 'event', 'posting_date']),
        ])->layout('components.layouts.admin', ['title' => 'Accounting Pilot']);
    }

    private function authorizePage(): void
    {
        abort_unless(AccountingPilot::enabled(), 404);
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
    }
}
