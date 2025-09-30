<div class="flex flex-col items-start p-1">
    <span class="font-medium text-xs" x-text="event.title"></span>
    <div class="text-xs opacity-75" x-show="event.extendedProps.description">
        <span x-text="event.extendedProps.description"></span>
    </div>
    <div class="text-xs opacity-75" x-show="event.extendedProps.hours">
        <span x-text="event.extendedProps.hours + ' hours'"></span>
    </div>
</div>
