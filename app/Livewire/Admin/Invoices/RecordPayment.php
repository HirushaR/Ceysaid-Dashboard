<?php

namespace App\Livewire\Admin\Invoices;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class RecordPayment extends Component
{
    public Invoice $invoice;
    public $amount;
    public string $payment_date;
    public string $payment_method = 'bank_transfer';
    public string $deposit_to = 'cash';
    public string $notes = '';

    public function mount(): void { abort_unless(auth()->user()->canManageAccountingRecords(), 403); $this->invoice->load('customerPayments'); $this->amount = $this->invoice->customer_balance_amount; $this->payment_date = now()->toDateString(); }

    public function save()
    {
        $data = $this->validate(['amount' => ['required','numeric','gt:0'], 'payment_date' => ['required','date','before_or_equal:today'], 'payment_method' => ['required','in:'.implode(',', array_keys(PaymentMode::options()))], 'deposit_to' => ['required','in:'.implode(',', array_keys(DepositAccount::options()))], 'notes' => ['nullable','string','max:5000']]);
        DB::transaction(function () use ($data): void {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($this->invoice->id);
            $outstanding = round((float) $invoice->total_amount - (float) $invoice->customerPayments()->sum('amount'), 2);
            if ((float) $data['amount'] > $outstanding + 0.0001) throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding balance of LKR '.number_format($outstanding, 2).'.']);
            $invoice->customerPayments()->create($data);
        });
        session()->flash('success', 'Customer receipt recorded.');
        return $this->redirectRoute('admin.invoices.show', $this->invoice, navigate: true);
    }

    public function render() { return view('livewire.admin.invoices.record-payment', ['modes' => PaymentMode::options(), 'accounts' => DepositAccount::options()])->layout('components.layouts.admin', ['title' => 'Record receipt']); }
}
