<div class="mx-auto max-w-5xl space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Vendor bills / Air tickets</p><h1>{{ $request ? 'Correct and requeue ticket' : 'Queue to issue' }}</h1><p>{{ $vendorBill->vendor_bill_number }} · {{ $vendorBill->invoice?->lead?->customer_name ?? 'No linked customer' }}</p></div>
        <a href="{{ route('admin.vendor-bills.show',$vendorBill) }}" class="btn-secondary">Cancel</a>
    </div>

    @if($request?->decision_notes)<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><strong>Accounts feedback:</strong> {{ $request->decision_notes }}</div>@endif

    <div class="grid gap-6 lg:grid-cols-[.85fr_1.15fr]">
        <section class="panel p-5">
            <p class="section-label">Vendor bill information</p>
            <dl class="mt-5 space-y-4">
                <div><dt>Vendor bill</dt><dd>{{ $vendorBill->vendor_bill_number }}</dd></div>
                <div><dt>Customer invoice</dt><dd>{{ $vendorBill->invoice?->invoice_number ?? 'Not attached' }}</dd></div>
                <div><dt>Supplier</dt><dd>{{ $vendorBill->supplier?->name ?? $vendorBill->vendor_name }}</dd></div>
                <div><dt>Service type</dt><dd>{{ $vendorBill->service_type }}</dd></div>
                <div><dt>Service details</dt><dd class="whitespace-pre-line">{{ $vendorBill->service_details ?: 'Not provided' }}</dd></div>
                <div><dt>Vendor bill amount</dt><dd class="text-lg">LKR {{ number_format((float)$vendorBill->bill_amount,2) }}</dd></div>
            </dl>
        </section>

        <form wire:submit="save" class="panel p-5">
            <p class="section-label">Ticket request</p><h2 class="mt-1 text-lg font-bold">Booking and time-limit details</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><label class="form-label">PCC / Provider *</label><select wire:model="provider" class="form-input"><option value="">Select provider</option>@foreach($providers as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('provider')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Booking reference (PNR) *</label><input wire:model="booking_reference" class="form-input" placeholder="Enter booking reference">@error('booking_reference')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Airline *</label><input wire:model="airline" class="form-input" placeholder="Enter airline">@error('airline')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Time limit *</label><input wire:model="time_limit" type="datetime-local" class="form-input">@error('time_limit')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="form-label">Total amount</label><div class="form-input bg-slate-50 font-bold dark:bg-slate-800">LKR {{ number_format((float)$vendorBill->bill_amount,2) }}</div><p class="mt-1 text-xs text-slate-500">Locked to the vendor bill amount.</p></div>
            </div>
            <div class="mt-6 flex justify-end"><button class="btn-primary">{{ $request ? 'Requeue for approval' : 'Queue to issue' }}</button></div>
        </form>
    </div>
</div>
