<?php

namespace App\Livewire\Admin\VendorBills;

use App\Models\VendorBill;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = '';
    public function mount(): void { abort_unless(auth()->user()->canViewVendorBills(), 403); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function render()
    {
        $user = auth()->user();
        $bills = VendorBill::query()->with(['supplier','invoice.lead'])
            ->when(! $user->canManageAccountingRecords(), fn ($q) => $q->where(fn ($q) => $q
                ->whereNull('invoice_id')
                ->orWhereHas('invoice.lead', fn ($lead) => $user->isSales() ? $lead->where('assigned_to', $user->id) : $lead->where('assigned_operator', $user->id))))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('vendor_bill_number','like','%'.$this->search.'%')->orWhereHas('supplier', fn ($q) => $q->where('name','like','%'.$this->search.'%'))))->latest()->paginate(20);
        return view('livewire.admin.vendor-bills.index', compact('bills'))->layout('components.layouts.admin', ['title' => 'Vendor bills']);
    }
}
