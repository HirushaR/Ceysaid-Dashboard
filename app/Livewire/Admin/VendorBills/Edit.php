<?php

namespace App\Livewire\Admin\VendorBills;

use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Models\VendorBillLineItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Edit extends Component
{
    public VendorBill $vendorBill;
    public ?int $invoice_id = null;
    public ?int $supplier_id = null;
    public ?string $due_date = null;
    public string $service_type = '';
    public string $service_details = '';
    public string $notes = '';
    public array $lines = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('update', $this->vendorBill), 403);
        $this->vendorBill->load(['lineItems', 'vendorBillPayments']);
        $this->invoice_id = $this->vendorBill->invoice_id;
        $this->supplier_id = $this->vendorBill->supplier_id;
        $this->due_date = $this->vendorBill->due_date?->toDateString();
        $this->service_type = $this->vendorBill->service_type ?? '';
        $this->service_details = $this->vendorBill->service_details ?? '';
        $this->notes = $this->vendorBill->notes ?? '';
        $this->lines = $this->vendorBill->lineItems->map(fn ($line) => [
            'description' => $line->description,
            'quantity' => $line->quantity,
            'rate' => $line->rate,
        ])->values()->all();

        if ($this->lines === []) {
            $this->lines = [['description' => $this->service_type ?: 'Service', 'quantity' => 1, 'rate' => $this->vendorBill->bill_amount]];
        }
    }

    public function addLine(): void
    {
        $this->lines[] = ['description' => '', 'quantity' => 1, 'rate' => 0];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) > 1) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines);
        }
    }

    public function save()
    {
        abort_unless(auth()->user()->can('update', $this->vendorBill), 403);
        $data = $this->validate([
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'due_date' => ['required', 'date'],
            'service_type' => ['required', 'string', 'max:255'],
            'service_details' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:2000'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.rate' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = ! empty($data['invoice_id']) ? Invoice::with('lead')->findOrFail($data['invoice_id']) : null;
        if ($invoice && ! auth()->user()->canViewInvoice($invoice)) {
            throw ValidationException::withMessages(['invoice_id' => 'You cannot attach this vendor bill to that invoice.']);
        }

        DB::transaction(function () use ($data) {
            $bill = VendorBill::query()->lockForUpdate()->findOrFail($this->vendorBill->id);
            $previousInvoice = $bill->invoice;
            $total = VendorBillLineItem::sumAmountsFromFormArray($data['lines']);
            $paid = round((float) $bill->vendorBillPayments()->sum('amount'), 2);

            if ($total + 0.0001 < $paid) {
                throw ValidationException::withMessages(['lines' => 'Bill total cannot be below the amount already paid: LKR '.number_format($paid, 2).'.']);
            }

            $supplier = Supplier::findOrFail($data['supplier_id']);
            $bill->update([
                'invoice_id' => $data['invoice_id'] ?: null,
                'supplier_id' => $supplier->id,
                'vendor_name' => $supplier->name,
                'due_date' => $data['due_date'],
                'service_type' => $data['service_type'],
                'service_details' => $data['service_details'],
                'notes' => $data['notes'],
                'bill_amount' => $total,
            ]);
            $bill->lineItems()->delete();
            foreach ($data['lines'] as $index => $line) {
                $bill->lineItems()->create($line + ['sort_order' => $index]);
            }
            $bill->refresh()->recalculateFromPayments();
            if ($previousInvoice && $previousInvoice->id !== $bill->invoice_id) {
                $previousInvoice->updateVendorPaymentStatus();
            }
        });

        session()->flash('success', 'Vendor bill updated.');

        return $this->redirectRoute('admin.vendor-bills.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.vendor-bills.edit', [
            'invoices' => Invoice::with('lead')->visibleToUser(auth()->user())->latest()->limit(200)->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ])
            ->layout('components.layouts.admin', ['title' => 'Edit '.$this->vendorBill->vendor_bill_number]);
    }
}
