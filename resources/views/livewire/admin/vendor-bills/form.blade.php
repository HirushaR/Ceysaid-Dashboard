<div class="mx-auto max-w-5xl space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Finance / Vendor bills</p><h1>{{ $heading }}</h1><p>Record a supplier invoice now and optionally attach it to a customer invoice.</p></div>
        <a href="{{ route('admin.vendor-bills.index') }}" class="btn-secondary">Cancel</a>
    </div>
    <form wire:submit="save" class="space-y-6">
        <section class="panel p-5">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label">Customer invoice <span class="text-slate-400">(optional)</span></label>
                    <select wire:model="invoice_id" class="form-input">
                        <option value="">Attach later</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} · {{ $invoice->lead?->customer_name }}</option>
                        @endforeach
                    </select>
                    @error('invoice_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label">Supplier *</label>
                    <select wire:model="supplier_id" class="form-input"><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>
                    @error('supplier_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div><label class="form-label">Due date *</label><input wire:model="due_date" type="date" class="form-input">@error('due_date')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Service type *</label><input wire:model="service_type" class="form-input">@error('service_type')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2"><label class="form-label">Service details</label><textarea wire:model="service_details" class="form-input"></textarea></div>
            </div>
        </section>
        <section class="panel">
            <div class="panel-header"><h2>Bill lines</h2><button type="button" wire:click="addLine" class="btn-secondary">+ Add line</button></div>
            <div class="space-y-3 p-5">
                @foreach($lines as $index => $line)
                    <div class="grid gap-3 rounded-xl bg-slate-50 p-3 md:grid-cols-[1fr_120px_160px_44px] dark:bg-slate-800">
                        <input wire:model="lines.{{ $index }}.description" class="form-input" placeholder="Description">
                        <input wire:model="lines.{{ $index }}.quantity" type="number" min=".01" step=".01" class="form-input">
                        <input wire:model="lines.{{ $index }}.rate" type="number" min="0" step=".01" class="form-input">
                        <button type="button" wire:click="removeLine({{ $index }})" class="btn-icon text-rose-600">×</button>
                    </div>
                @endforeach
                @error('lines.*.description')<p class="form-error">Every line needs a description.</p>@enderror
            </div>
        </section>
        <div class="flex justify-end"><button class="btn-primary">{{ $submitLabel }}</button></div>
    </form>
</div>
