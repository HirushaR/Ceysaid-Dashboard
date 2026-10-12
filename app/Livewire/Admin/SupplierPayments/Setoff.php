<?php

namespace App\Livewire\Admin\SupplierPayments;

use App\Models\SupplierPayment;
use App\Models\VendorBill;
use App\Services\UpdateSupplierPaymentService;
use Livewire\Component;

class Setoff extends Component
{
    public SupplierPayment $supplierPayment;

    public array $allocations = [];

    public string $audit_reason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $this->supplierPayment->load(['supplier', 'allocations']);
        $current = $this->supplierPayment->allocations->keyBy('vendor_bill_id');
        $this->allocations = VendorBill::query()
            ->where('supplier_id', $this->supplierPayment->supplier_id)
            ->with('vendorBillPayments')
            ->get()
            ->filter(fn (VendorBill $bill): bool => $bill->outstanding_amount > 0 || $current->has($bill->id))
            ->map(fn (VendorBill $bill): array => [
                'vendor_bill_id' => $bill->id,
                'number' => $bill->vendor_bill_number,
                'is_opening_balance' => $bill->is_opening_balance,
                'available' => round($bill->outstanding_amount + (float) ($current->get($bill->id)?->amount ?? 0), 2),
                'amount' => (float) ($current->get($bill->id)?->amount ?? 0),
            ])->values()->all();
    }

    public function useAvailable(int $index): void
    {
        $remaining = max(0, (float) $this->supplierPayment->amount - collect($this->allocations)->except($index)->sum(fn (array $row): float => (float) $row['amount']));
        $this->allocations[$index]['amount'] = min((float) $this->allocations[$index]['available'], $remaining);
    }

    public function save(UpdateSupplierPaymentService $service)
    {
        $data = $this->validate([
            'allocations' => ['array'],
            'allocations.*.vendor_bill_id' => ['required', 'integer'],
            'allocations.*.amount' => ['nullable', 'numeric', 'min:0'],
            'audit_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $allocations = collect($data['allocations'])->filter(fn (array $row): bool => (float) $row['amount'] > 0)->map(fn (array $row): array => ['vendor_bill_id' => $row['vendor_bill_id'], 'amount' => $row['amount']])->values()->all();
        $service->update($this->supplierPayment, [
            'payment_date' => $this->supplierPayment->payment_date->toDateString(),
            'amount' => $this->supplierPayment->amount,
            'payment_mode' => $this->supplierPayment->payment_mode,
            'paid_through' => $this->supplierPayment->paid_through,
            'reference_number' => $this->supplierPayment->reference_number,
            'notes' => $this->supplierPayment->notes,
            'audit_reason' => $data['audit_reason'],
            'allocations' => $allocations,
        ]);
        session()->flash('success', 'Vendor payment setoff updated.');

        return $this->redirectRoute('admin.payments.supplier.show', $this->supplierPayment, navigate: true);
    }

    public function render()
    {
        $allocated = collect($this->allocations)->sum(fn (array $row): float => (float) $row['amount']);

        return view('livewire.admin.supplier-payments.setoff', [
            'allocated' => $allocated,
            'unapplied' => max(0, (float) $this->supplierPayment->amount - $allocated),
        ])->layout('components.layouts.admin', ['title' => 'Vendor payment setoff']);
    }
}
