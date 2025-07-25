<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🚀 Leave Request Center
        </x-slot>
        
        <x-slot name="description">
            Need time off? Submit your leave request in just a few clicks!
        </x-slot>

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-700">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">Submit Leave Request</h3>
                            <p class="text-sm text-green-700 dark:text-green-300">Quick and easy form</p>
                        </div>
                    </div>
                    <p class="text-sm text-green-700 dark:text-green-300 mb-4">Submit a new leave request for vacation, sick days, personal time, or other needs.</p>
                    {{ $createAction }}
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">My Requests</h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300">Track status and history</p>
                        </div>
                    </div>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-4">View all your leave requests, check approval status, and manage pending requests.</p>
                    {{ $viewAction }}
                </div>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">💡 Quick Tips</h4>
                        <ul class="mt-2 text-xs text-yellow-700 dark:text-yellow-300 space-y-1">
                            <li>• Submit requests at least 2 weeks in advance for planned leave</li>
                            <li>• Emergency requests will be reviewed as soon as possible</li>
                            <li>• You can edit pending requests until they are approved</li>
                            <li>• Check your email for approval notifications</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 