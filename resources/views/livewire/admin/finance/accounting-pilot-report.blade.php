@php use App\Services\AccountingPilot; @endphp
<div class="space-y-6">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Finance</p>
            <h1>Accounting Pilot</h1>
            <p>Posted pilot journals only · LKR · Closed through {{ $closedThrough ?? 'no period closed' }}</p>
        </div>
        <a class="btn-secondary" href="{{ route('admin.invoices.index') }}">Find a booking</a>
    </div>

    @if ($errors->any())
        <ul class="panel space-y-1 p-4 text-sm text-red-700" role="alert">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif
    @if (session('success'))
        <p class="panel p-4 text-sm" role="status">{{ session('success') }}</p>
    @endif

    <section class="panel space-y-4 p-5">
        <h2 class="font-bold">Trial balance at {{ $reportDate }}</h2>
        <form wire:submit="runReport" class="flex flex-wrap items-end gap-2">
            <label>
                <span class="block text-sm font-semibold">As of</span>
                <input type="date" wire:model="asOf" class="form-input" required>
            </label>
            <button class="btn-secondary" type="submit">Run report</button>
        </form>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Account</th><th>Debit movement</th><th>Credit movement</th><th>Net debit / (credit)</th></tr></thead>
                <tbody>
                    @forelse ($balance as $row)
                        <tr>
                            <td>{{ AccountingPilot::ACCOUNTS[$row->account_code] ?? $row->account_code }}</td>
                            <td>{{ AccountingPilot::money($row->debit_minor) }}</td>
                            <td>{{ AccountingPilot::money($row->credit_minor) }}</td>
                            <td>{{ AccountingPilot::money($row->debit_minor - $row->credit_minor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No posted journals through this date.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr>
                    <th>Total</th>
                    <th>{{ AccountingPilot::money($balance->sum('debit_minor')) }}</th>
                    <th>{{ AccountingPilot::money($balance->sum('credit_minor')) }}</th>
                    <th>{{ AccountingPilot::money($balance->sum('debit_minor') - $balance->sum('credit_minor')) }}</th>
                </tr></tfoot>
            </table>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="panel space-y-3 p-5">
            <h2 class="font-bold">Customer balances by booking</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Booking</th><th>Receivable</th><th>Advances / liabilities</th></tr></thead>
                    <tbody>
                        @forelse ($customerBalances as $row)
                            <tr>
                                <td><a class="font-semibold text-blue-600" href="{{ route('admin.invoices.accounting', $row->id) }}">{{ $row->invoice_number }}</a></td>
                                <td>{{ AccountingPilot::money($row->receivable_minor) }}</td>
                                <td>{{ AccountingPilot::money($row->advance_minor) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No customer balances.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr>
                        <th>Total</th>
                        <th>{{ AccountingPilot::money($customerBalances->sum('receivable_minor')) }}</th>
                        <th>{{ AccountingPilot::money($customerBalances->sum('advance_minor')) }}</th>
                    </tr></tfoot>
                </table>
            </div>
        </section>
        <section class="panel space-y-3 p-5">
            <h2 class="font-bold">Accrued tour costs by supplier</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Supplier</th><th>Accrued costs</th></tr></thead>
                    <tbody>
                        @forelse ($supplierAccruals as $row)
                            <tr><td>{{ $row->name }}</td><td>{{ AccountingPilot::money($row->balance_minor) }}</td></tr>
                        @empty
                            <tr><td colspan="2">No supplier accruals.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th>Total</th><th>{{ AccountingPilot::money($supplierAccruals->sum('balance_minor')) }}</th></tr></tfoot>
                </table>
            </div>
        </section>
    </div>

    <section class="panel space-y-3 p-5">
        <h2 class="font-bold">Awaiting approval</h2>
        @forelse ($pending as $row)
            <p class="text-sm">
                <a class="font-semibold text-blue-600" href="{{ route('admin.invoices.accounting', $row->invoice_id) }}">{{ $row->invoice_number }}</a>
                · {{ AccountingPilot::EVENTS[$row->event] }} · {{ $row->posting_date }}
            </p>
        @empty
            <p class="text-sm">No pending journals.</p>
        @endforelse
    </section>

    <section class="panel space-y-3 p-5">
        <h2 class="font-bold">Receipts and delivery to review through {{ $reportDate }}</h2>
        @forelse ($outstandingWork as $row)
            <p class="text-sm">
                <a class="font-semibold text-blue-600" href="{{ route('admin.invoices.accounting', $row->id) }}">{{ $row->invoice_number }}</a>
                · {{ $row->task }} · {{ $row->date }}
            </p>
        @empty
            <p class="text-sm">No outstanding receipts or deliveries.</p>
        @endforelse
    </section>

    @if (auth()->user()->isAdmin())
        <form wire:submit="close" class="panel space-y-4 p-5">
            <h2 class="font-bold">Close pilot through month end</h2>
            <p class="text-sm">Record the review of the trial balance, receipt evidence, delivery and supplier accruals. Closing locks pilot posting dates through the selected month end.</p>
            <label class="block">
                <span class="block text-sm font-semibold">Month-end date</span>
                <input type="date" wire:model="closeThrough" class="form-input" required>
            </label>
            <label class="block">
                <span class="block text-sm font-semibold">Reconciliation and approval reference</span>
                <textarea wire:model="closeEvidence" class="form-input w-full" required maxlength="2000"></textarea>
            </label>
            <button class="btn-primary" type="submit" wire:confirm="Close pilot posting dates through the selected month end?">Close pilot period</button>
        </form>
    @endif
</div>
