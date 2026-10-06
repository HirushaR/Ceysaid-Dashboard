<?php

namespace App\Livewire\Admin\Tours;

use App\Models\Tour;
use Livewire\Component;

class Show extends Component
{
    public Tour $tour;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isAccount(), 403);
        $this->tour->load([
            'leads.assignedUser',
            'invoices.customerPayments',
            'invoices.vendorBills.vendorBillPayments',
            'vendorBills.supplier',
            'vendorBills.vendorBillPayments',
        ]);
    }

    public function render()
    {
        $revenue = (float) $this->tour->invoices->sum('total_amount');
        $received = (float) $this->tour->invoices->sum(fn ($invoice) => (float) $invoice->payment_amount);
        $invoiceBills = $this->tour->invoices->flatMap->vendorBills;
        $allBills = $invoiceBills->concat($this->tour->vendorBills)->unique('id');
        $supplierCosts = (float) $allBills->sum('bill_amount');
        $supplierPaid = (float) $allBills->sum(fn ($bill) => $bill->total_paid_amount);
        $summary = compact('revenue', 'received', 'supplierCosts', 'supplierPaid') + [
            'receivable' => $revenue - $received,
            'payable' => $supplierCosts - $supplierPaid,
            'profit' => $revenue - $supplierCosts,
        ];

        return view('livewire.admin.tours.show', compact('summary'))
            ->layout('components.layouts.admin', ['title' => $this->tour->tour_code]);
    }
}
