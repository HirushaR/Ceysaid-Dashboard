<?php

namespace App\Livewire\Admin\Suppliers;

use App\Models\Supplier;
use App\Services\SupplierOpeningBalanceService;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $address = '';

    public string $tax_id = '';

    public string $contact_name = '';

    public string $phone = '';

    public string $email = '';

    public string $bank_details = '';

    public $opening_balance = 0;

    public string $opening_balance_date = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageAccountingRecords(), 403);
        $this->opening_balance_date = now()->toDateString();
    }

    public function save(SupplierOpeningBalanceService $openingBalances)
    {
        $data = $this->validate($this->rules());
        $supplier = Supplier::create(collect($data)->except(['opening_balance', 'opening_balance_date'])->all());
        $openingBalances->sync($supplier, (float) $data['opening_balance'], $data['opening_balance_date']);
        session()->flash('success', 'Supplier created.');

        return $this->redirectRoute('admin.suppliers.show', $supplier, navigate: true);
    }

    private function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:5000'], 'tax_id' => ['nullable', 'string', 'max:255'], 'contact_name' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'bank_details' => ['nullable', 'string', 'max:5000'], 'opening_balance' => ['nullable', 'numeric', 'min:0'], 'opening_balance_date' => ['required', 'date']];
    }

    public function render()
    {
        return view('livewire.admin.suppliers.form', ['heading' => 'New supplier', 'submitLabel' => 'Create supplier'])->layout('components.layouts.admin', ['title' => 'New supplier']);
    }
}
