<x-filament-panels::page>
    <style>
        .payment-register-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 1024px) {
            .payment-register-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .payment-register-summary {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <form wire:submit="applyFilters">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Apply filters
            </x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="resetFilters">
                Today
            </x-filament::button>
        </div>
    </form>

    @php
        $summary = $this->getSummary();
        $payments = $this->getPayments();
    @endphp

    <div class="payment-register-summary">
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Customer receipts</div>
            <div class="mt-1 text-2xl font-semibold text-success-600">LKR {{ number_format($summary['received'], 2) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Vendor payments</div>
            <div class="mt-1 text-2xl font-semibold text-danger-600">LKR {{ number_format($summary['paid'], 2) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Net movement</div>
            <div @class([
                'mt-1 text-2xl font-semibold',
                'text-success-600' => $summary['net'] >= 0,
                'text-danger-600' => $summary['net'] < 0,
            ])>LKR {{ number_format($summary['net'], 2) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Transactions</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($summary['count']) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                        <th class="px-3 py-3 font-medium">Payment date</th>
                        <th class="px-3 py-3 font-medium">Direction</th>
                        <th class="px-3 py-3 font-medium">Reference</th>
                        <th class="px-3 py-3 font-medium">Invoice</th>
                        <th class="px-3 py-3 font-medium">Lead</th>
                        <th class="px-3 py-3 font-medium">Customer / supplier</th>
                        <th class="px-3 py-3 font-medium">Method</th>
                        <th class="px-3 py-3 font-medium">Account</th>
                        <th class="px-3 py-3 text-right font-medium">Amount</th>
                        <th class="px-3 py-3 font-medium">Entered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="whitespace-nowrap px-3 py-3">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                            <td class="px-3 py-3">
                                <x-filament::badge :color="$payment->direction === 'in' ? 'success' : 'danger'">
                                    {{ $payment->direction === 'in' ? 'IN' : 'OUT' }}
                                </x-filament::badge>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $payment->reference ?: '#'.$payment->id }}</td>
                            <td class="whitespace-nowrap px-3 py-3">
                                @if ($payment->invoice_id)
                                    <a class="text-primary-600 hover:underline" href="{{ \App\Filament\Resources\InvoiceResource::getUrl('view', ['record' => $payment->invoice_id]) }}">
                                        {{ $payment->invoice_number }}
                                    </a>
                                @elseif ($payment->supplier_payment_id)
                                    <a class="text-primary-600 hover:underline" href="{{ \App\Filament\Resources\SupplierPaymentResource::getUrl('view', ['record' => $payment->supplier_payment_id]) }}">
                                        Multiple bills
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $payment->lead_reference ?: '#'.$payment->lead_id }}</td>
                            <td class="px-3 py-3">{{ $payment->party ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ \App\Enums\PaymentMode::tryFrom((string) $payment->payment_method)?->label() ?? $payment->payment_method ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ \App\Enums\DepositAccount::tryFrom((string) $payment->account)?->label() ?? $payment->account ?? '—' }}</td>
                            <td @class([
                                'whitespace-nowrap px-3 py-3 text-right font-semibold',
                                'text-success-600' => $payment->direction === 'in',
                                'text-danger-600' => $payment->direction === 'out',
                            ])>
                                {{ $payment->direction === 'in' ? '+' : '−' }} LKR {{ number_format((float) $payment->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-gray-500">{{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-10 text-center text-gray-500">
                                No payments were found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
