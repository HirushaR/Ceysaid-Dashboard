<?php

namespace App\Livewire\Admin\Finance;

use App\Models\FinancialAccount;
use App\Services\BankStatementService;
use Livewire\Attributes\Url;
use Livewire\Component;

class BankStatements extends Component
{
    #[Url]
    public string $account = 'ntb_current';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    public $opening_balance = null;

    public string $opening_balance_date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        if ($this->date_from === '') {
            $this->date_from = now()->startOfMonth()->toDateString();
        }
        if ($this->date_to === '') {
            $this->date_to = now()->toDateString();
        }
        $this->loadOpeningBalance();
    }

    public function updatedAccount(): void
    {
        $this->loadOpeningBalance();
    }

    public function saveOpeningBalance(): void
    {
        $data = $this->validate([
            'opening_balance' => ['required', 'numeric'],
            'opening_balance_date' => ['required', 'date'],
        ]);
        FinancialAccount::query()->where('code', $this->account)->firstOrFail()->update($data);
        session()->flash('success', 'Opening balance updated.');
    }

    private function loadOpeningBalance(): void
    {
        $account = FinancialAccount::query()->where('code', $this->account)->firstOrFail();
        $this->opening_balance = $account->opening_balance;
        $this->opening_balance_date = $account->opening_balance_date?->toDateString() ?? now()->startOfYear()->toDateString();
    }

    public function render(BankStatementService $service)
    {
        $this->validateOnly('date_from', ['date_from' => ['required', 'date']]);
        $this->validateOnly('date_to', ['date_to' => ['required', 'date', 'after_or_equal:date_from']]);

        return view('livewire.admin.finance.bank-statements', [
            'accounts' => FinancialAccount::query()->where('is_active', true)->orderBy('name')->get(),
            'statement' => $service->statement($this->account, $this->date_from, $this->date_to),
        ])->layout('components.layouts.admin', ['title' => 'Bank statements']);
    }
}
