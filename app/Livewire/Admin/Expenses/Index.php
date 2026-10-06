<?php

namespace App\Livewire\Admin\Expenses;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Models\Expense;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $expense_date = '';

    public string $category = '';

    public string $description = '';

    public $amount = null;

    public string $payment_mode = 'bank_transfer';

    public string $paid_through = 'cash';

    public string $reference_number = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $this->expense_date = now()->toDateString();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $data = $this->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['required', 'in:'.implode(',', array_keys(PaymentMode::options()))],
            'paid_through' => ['required', 'in:'.implode(',', array_keys(DepositAccount::options()))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['created_by'] = auth()->id();
        Expense::create($data);
        $this->reset(['category', 'description', 'amount', 'reference_number', 'notes']);
        $this->resetPage();
        session()->flash('success', 'Expense added to the finance register.');
    }

    public function render()
    {
        return view('livewire.admin.expenses.index', [
            'expenses' => Expense::with('creator')->latest('expense_date')->latest('id')->paginate(25),
            'modes' => PaymentMode::options(),
            'accounts' => DepositAccount::options(),
        ])->layout('components.layouts.admin', ['title' => 'Expenses']);
    }
}
