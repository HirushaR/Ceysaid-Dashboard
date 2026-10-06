<div class="mx-auto max-w-4xl space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Finance / Payments</p><h1>Record supplier payment</h1><p>Record a direct supplier payment or allocate it across outstanding vendor bills.</p></div>
        <a href="{{ route('admin.payments.index') }}" class="btn-secondary">Cancel</a>
    </div>
    <form wire:submit="save" class="space-y-6">
        <section class="panel p-5">
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="form-label">Supplier *</label><select wire:model.live="supplier_id" class="form-input"><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>@error('supplier_id')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Payment date *</label><input wire:model="payment_date" type="date" max="{{ now()->toDateString() }}" class="form-input"></div>
                <div><label class="form-label">Payment total *</label><input wire:model="amount" type="number" step=".01" min=".01" class="form-input">@error('amount')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Method</label><select wire:model="payment_mode" class="form-input">@foreach($modes as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div><label class="form-label">Paid through</label><select wire:model="paid_through" class="form-input">@foreach($accounts as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div><label class="form-label">Reference</label><input wire:model="reference_number" class="form-input"></div>
                <label class="md:col-span-2 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30"><input wire:model.live="without_vendor_bill" type="checkbox" class="mt-1"><span><strong class="block">Payment without vendor bill</strong><span class="text-sm text-slate-600 dark:text-slate-300">Use this for a direct or advance supplier payment. No bill allocation is required.</span></span></label>
            </div>
        </section>
        @unless($without_vendor_bill)
            <section class="panel">
                <div class="panel-header"><div><h2>Bill allocations</h2><p>The allocated total must equal the payment total</p></div></div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($allocations as $index=>$allocation)
                        <div class="grid items-center gap-3 p-4 sm:grid-cols-[1fr_180px_110px]"><div><p class="font-semibold">{{ $allocation['number'] }}</p><p class="text-sm text-slate-500">Outstanding LKR {{ number_format($allocation['outstanding'],2) }}</p></div><input wire:model="allocations.{{ $index }}.amount" type="number" min="0" step=".01" class="form-input" aria-label="Allocation"><button type="button" wire:click="useOutstanding({{ $index }})" class="btn-secondary">Pay balance</button></div>
                    @empty
                        <div class="empty-state">Select a supplier to see outstanding bills.</div>
                    @endforelse
                </div>
                @error('allocations')<p class="form-error p-4">{{ $message }}</p>@enderror
            </section>
        @endunless
        <div class="flex justify-end"><button class="btn-primary">Record payment</button></div>
    </form>
</div>
