<div class="p-6">
    <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">{{ $this->getHeading() }}</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                    @foreach ($this->getColumns() as $column)
                        <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($this->getRows() as $row)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        @foreach ($row as $cell)
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($this->getColumns()) }}" class="px-3 py-6 text-center text-gray-500">
                            No data for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
