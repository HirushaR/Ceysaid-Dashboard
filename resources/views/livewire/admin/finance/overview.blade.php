<div class="space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Finance</p><h1>Finance Overview</h1><p>Upcoming customer receipts and supplier payments as at {{ $asOf->format('d M Y') }}.</p></div>
    </div>

    <section class="grid gap-5 xl:grid-cols-3" aria-label="Cash-flow forecast">
        @foreach($forecast as $window)
            <article class="panel p-5">
                <div class="flex items-start justify-between gap-3"><div><p class="section-label">{{ $window['label'] }}</p><h2 class="mt-1 font-bold">Through {{ $window['end'] }}</h2></div><span @class(['status-badge',$window['net']>=0?'status-success':'status-danger'])>{{ $window['net']>=0?'Positive':'Funding gap' }}</span></div>
                <dl class="mt-5 space-y-3"><div class="flex items-center justify-between"><dt>Money coming in</dt><dd class="font-bold text-emerald-600">LKR {{ number_format($window['incoming'],2) }}</dd></div><div class="flex items-center justify-between"><dt>Money going out</dt><dd class="font-bold text-rose-600">LKR {{ number_format($window['outgoing'],2) }}</dd></div><div class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800"><dt>Expected net</dt><dd @class(['text-lg font-bold',$window['net']>=0?'text-emerald-600':'text-rose-600'])>LKR {{ number_format($window['net'],2) }}</dd></div></dl>
            </article>
        @endforeach
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        <article class="panel border-amber-200 p-5 dark:border-amber-900"><p class="section-label">Overdue receipts</p><div class="mt-2 flex items-end justify-between gap-4"><strong class="text-2xl text-amber-700 dark:text-amber-400">LKR {{ number_format($overdue['incoming'],2) }}</strong><span class="status-badge status-warning">{{ $overdue['incoming_count'] }} invoices</span></div><p class="mt-2 text-sm text-slate-500">Customer money that should already have been received.</p></article>
        <article class="panel border-rose-200 p-5 dark:border-rose-900"><p class="section-label">Overdue payments</p><div class="mt-2 flex items-end justify-between gap-4"><strong class="text-2xl text-rose-700 dark:text-rose-400">LKR {{ number_format($overdue['outgoing'],2) }}</strong><span class="status-badge status-danger">{{ $overdue['outgoing_count'] }} bills</span></div><p class="mt-2 text-sm text-slate-500">Supplier money that should already have been paid.</p></article>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel"><div class="panel-header"><div><h2>Next payments to receive</h2><p>Open customer invoices ordered by due date</p></div><a href="{{ route('admin.receivables.index') }}" class="text-sm font-semibold text-blue-600">View all</a></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Due</th><th>Invoice / Customer</th><th>Outstanding</th></tr></thead><tbody>@forelse($receivables as $invoice)<tr><td class="whitespace-nowrap font-semibold">{{ $invoice->due_date?->format('d M Y') ?? 'No date' }}</td><td><a class="table-link" href="{{ route('admin.invoices.show',$invoice) }}">{{ $invoice->invoice_number }}</a><p class="mt-1 text-xs text-slate-500">{{ $invoice->lead?->customer_name ?? 'No customer' }}</p></td><td class="whitespace-nowrap font-bold text-emerald-600">LKR {{ number_format((float)$invoice->balance_amount,2) }}</td></tr>@empty<tr><td colspan="3" class="empty-state">No upcoming customer receipts.</td></tr>@endforelse</tbody></table></div></section>
        <section class="panel"><div class="panel-header"><div><h2>Next payments to make</h2><p>Open supplier bills ordered by due date</p></div><a href="{{ route('admin.vendor-bills.index') }}" class="text-sm font-semibold text-blue-600">View all</a></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Due</th><th>Bill / Supplier</th><th>Outstanding</th></tr></thead><tbody>@forelse($payables as $bill)<tr><td class="whitespace-nowrap font-semibold">{{ $bill->due_date?->format('d M Y') ?? 'No date' }}</td><td><a class="table-link" href="{{ route('admin.vendor-bills.show',$bill) }}">{{ $bill->vendor_bill_number }}</a><p class="mt-1 text-xs text-slate-500">{{ $bill->supplier?->name ?? $bill->vendor_name }}</p></td><td class="whitespace-nowrap font-bold text-rose-600">LKR {{ number_format($bill->outstanding_amount,2) }}</td></tr>@empty<tr><td colspan="3" class="empty-state">No upcoming supplier payments.</td></tr>@endforelse</tbody></table></div></section>
    </div>
</div>
