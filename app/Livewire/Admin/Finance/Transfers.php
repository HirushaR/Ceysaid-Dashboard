<?php

namespace App\Livewire\Admin\Finance;

use App\Enums\DepositAccount;
use App\Models\InternalTransfer;
use App\Services\DocumentNumberService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Transfers extends Component
{
    use WithPagination;

    public string $transfer_date = '';

    public string $from_account = '';

    public string $to_account = '';

    public $amount = null;

    public string $reference_number = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $this->transfer_date = now()->toDateString();
    }

    public function save(DocumentNumberService $numbers): void
    {
        $accounts = array_keys(DepositAccount::options());
        $data = $this->validate([
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'from_account' => ['required', Rule::in($accounts), 'different:to_account'],
            'to_account' => ['required', Rule::in($accounts)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['transfer_number'] = $numbers->nextInternalTransferNumber();
        $data['created_by'] = auth()->id();
        InternalTransfer::create($data);
        $this->reset(['from_account', 'to_account', 'amount', 'reference_number', 'notes']);
        session()->flash('success', 'Internal transfer recorded. Both account ledgers have been updated.');
    }

    public function render()
    {
        return view('livewire.admin.finance.transfers', [
            'accounts' => DepositAccount::options(),
            'transfers' => InternalTransfer::with('creator')->latest('transfer_date')->latest('id')->paginate(25),
        ])->layout('components.layouts.admin', ['title' => 'Internal transfers']);
    }
}
