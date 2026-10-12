<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()->canViewSuppliers(), 403);
        $search = trim($this->search);
        $query = Supplier::query()->withCount('vendorBills')->when($search, fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
            $like = "%{$search}%";
            $query->where('name', 'like', $like)->orWhere('contact_name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)
                ->orWhereHas('vendorBills', fn (Builder $bill): Builder => $bill->where('vendor_bill_number', 'like', $like)->orWhereHas('invoice', fn (Builder $invoice): Builder => $invoice->where('invoice_number', 'like', $like)->orWhereHas('lead', fn (Builder $lead): Builder => $lead->where('customer_name', 'like', $like))));
        }));

        return view('livewire.admin.suppliers.index', ['suppliers' => $query->orderBy('name')->paginate(20)])->layout('components.layouts.admin', ['title' => 'Suppliers']);
    }
}
