<div class="space-y-6">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Finance</p>
            <h1>Vendor bills</h1>
            <p>Supplier invoices and outstanding payables.</p>
        </div>
        @can('create', \App\Models\VendorBill::class)
            <a href="{{ route('admin.vendor-bills.create') }}" class="btn-primary">New vendor bill</a>
        @endcan
    </div>

    <section class="panel">
        <div class="border-b border-slate-100 p-4 dark:border-slate-800">
            <input wire:model.live.debounce.300ms="search" class="form-input max-w-md" placeholder="Search bill or supplier">
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Bill</th><th>Supplier</th><th>Customer</th><th>Due</th><th>Status</th><th>Outstanding</th><th></th></tr></thead>
                <tbody>
                    @forelse($bills as $bill)
                        <tr>
                            <td><a href="{{ auth()->user()->can('update', $bill) ? route('admin.vendor-bills.edit', $bill) : route('admin.vendor-bills.show', $bill) }}" class="font-semibold text-blue-600">{{ $bill->vendor_bill_number }}</a></td>
                            <td>
                                @if($bill->supplier && auth()->user()->canViewSuppliers())
                                    <a href="{{ route('admin.suppliers.show', $bill->supplier) }}" class="hover:text-blue-600">{{ $bill->supplier->name }}</a>
                                @elseif($bill->supplier)
                                    {{ $bill->supplier->name }}
                                @else
                                    {{ $bill->vendor_name }}
                                @endif
                            </td>
                            <td>{{ $bill->invoice?->lead?->customer_name }}</td>
                            <td>{{ $bill->due_date?->format('d M Y') }}</td>
                            <td><span class="status-badge">{{ ucfirst($bill->payment_status) }}</span></td>
                            <td>LKR {{ number_format($bill->outstanding_amount, 2) }}</td>
                            <td><a target="_blank" href="{{ route('finance.vendor-bills.pdf', $bill) }}" class="text-sm font-semibold text-blue-600">PDF</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No vendor bills yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $bills->links() }}</div>
    </section>
</div>
