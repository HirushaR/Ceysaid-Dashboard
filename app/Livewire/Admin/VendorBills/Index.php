<?php

namespace App\Livewire\Admin\VendorBills;

use App\Models\VendorBill;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = '';
    public function mount(): void { abort_unless(auth()->user()->canManageAccountingRecords(), 403); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function render()
    {
        $bills = VendorBill::query()->with(['supplier','invoice.lead'])->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('vendor_bill_number','like','%'.$this->search.'%')->orWhereHas('supplier', fn ($q) => $q->where('name','like','%'.$this->search.'%'))))->latest()->paginate(20);
        return view('livewire.admin.vendor-bills.index', compact('bills'))->layout('components.layouts.admin', ['title' => 'Vendor bills']);
    }
}
