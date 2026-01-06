<div class="flex flex-col items-start p-1">
    <span class="font-medium text-xs" x-text="event.title"></span>
    <div class="text-xs opacity-75" x-show="event.extendedProps.description">
        <span x-text="event.extendedProps.description"></span>
    </div>
    <!-- Leave-specific fields -->
    <div class="text-xs opacity-75" x-show="event.extendedProps.hours">
        <span x-text="event.extendedProps.hours + ' hours'"></span>
    </div>
    <div class="text-xs opacity-75" x-show="event.extendedProps.user_name">
        <span x-text="event.extendedProps.user_name"></span>
    </div>
    <!-- Closure-specific fields -->
    <div class="text-xs opacity-75" x-show="event.extendedProps.type && event.extendedProps.closure_id">
        <span x-text="event.extendedProps.type"></span>
    </div>
</div>
