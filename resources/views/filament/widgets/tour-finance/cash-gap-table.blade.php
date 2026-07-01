<div class="p-6">
    <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">{{ $this->getHeading() }}</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">Tour code</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">Departure</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">Balance receivable</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">Vendor payable</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">Cash gap / surplus</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">Days to departure</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->getCashGapRows() as $row)
                    <tr @class([
                        'border-b border-gray-100 dark:border-gray-800',
                        'bg-danger-50 dark:bg-danger-950/30' => $row['is_urgent'],
                        'bg-warning-50 dark:bg-warning-950/20' => $row['is_negative'] && ! $row['is_urgent'],
                    ])>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $row['tour_code'] }}</td>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $row['departure_date'] ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">LKR {{ number_format($row['balance_receivable'], 2) }}</td>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">LKR {{ number_format($row['vendor_payable'], 2) }}</td>
                        <td @class([
                            'px-3 py-2 font-semibold',
                            'text-danger-600 dark:text-danger-400' => $row['cash_gap'] < 0,
                            'text-success-600 dark:text-success-400' => $row['cash_gap'] >= 0,
                        ])>
                            LKR {{ number_format($row['cash_gap'], 2) }}
                        </td>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $row['days_to_departure'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                            No tours match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
