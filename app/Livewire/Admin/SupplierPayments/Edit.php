<?php

namespace App\Livewire\Admin\SupplierPayments;

use App\Enums\DepositAccount;
use App\Enums\PaymentMode;
use App\Models\SupplierPayment;
use App\Models\VendorBill;
use App\Services\UpdateSupplierPaymentService;
use Livewire\Component;

class Edit extends Component
{
    public SupplierPayment $supplierPayment;

    public string $payment_date = '';

    public $amount = null;

    public string $payment_mode = '';

    public string $paid_through = '';

    public string $reference_number = '';

    public string $notes = '';

    public string $audit_reason = '';

    public bool $without_vendor_bill = false;

    public array $allocations = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $this->supplierPayment->load('allocations');
        $this->fill($this->supplierPayment->only(['payment_mode', 'paid_through', 'reference_number', 'notes']));
        $this->payment_date = $this->supplierPayment->payment_date->toDateString();
        $this->amount = $this->supplierPayment->amount;
        $this->without_vendor_bill = $this->supplierPayment->allocations->isEmpty();
        $current = $this->supplierPayment->allocations->keyBy('vendor_bill_id');
        $this->allocations = VendorBill::query()->where('supplier_id', $this->supplierPayment->supplier_id)->with('vendorBillPayments')->get()->filter(fn ($bill) => $bill->outstanding_amount > 0 || $current->has($bill->id))->map(fn ($bill) => ['vendor_bill_id' => $bill->id, 'number' => $bill->vendor_bill_number, 'outstanding' => round($bill->outstanding_amount + (float) ($current->get($bill->id)?->amount ?? 0), 2), 'amount' => (float) ($current->get($bill->id)?->amount ?? 0)])->values()->all();
    }

    public function useOutstanding(int $index): void
    {
        $this->allocations[$index]['amount'] = $this->allocations[$index]['outstanding'];
        $this->amount = collect($this->allocations)->sum(fn ($row) => (float) $row['amount']);
    }

    public function save(UpdateSupplierPaymentService $service)
    {
        $data = $this->validate(['payment_date' => ['required', 'date', 'before_or_equal:today'], 'amount' => ['required', 'numeric', 'gt:0'], 'payment_mode' => ['required', 'in:'.implode(',', array_keys(PaymentMode::options()))], 'paid_through' => ['required', 'in:'.implode(',', array_keys(DepositAccount::options()))], 'reference_number' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000'], 'audit_reason' => ['required', 'string', 'min:5', 'max:500'], 'allocations' => ['array'], 'allocations.*.vendor_bill_id' => ['required', 'integer'], 'allocations.*.amount' => ['nullable', 'numeric', 'min:0']]);
        $data['allocations'] = $this->without_vendor_bill ? [] : collect($data['allocations'])->filter(fn ($row) => (float) $row['amount'] > 0)->map(fn ($row) => ['vendor_bill_id' => $row['vendor_bill_id'], 'amount' => $row['amount']])->values()->all();
        $service->update($this->supplierPayment, $data);
        session()->flash('success', 'Supplier payment updated and affected vendor bills recalculated.');

        return $this->redirectRoute('admin.payments.supplier.show', $this->supplierPayment, navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.supplier-payments.edit', ['modes' => PaymentMode::options(), 'accounts' => DepositAccount::options()])->layout('components.layouts.admin', ['title' => 'Edit supplier payment']);
    }
}
