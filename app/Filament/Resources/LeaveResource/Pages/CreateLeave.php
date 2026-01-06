<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Services\LeaveAllocationService;
use App\Models\User;
use App\Enums\LeaveType;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate leave allocation limits
        $user = User::find($data['user_id']);
        $leaveType = LeaveType::from($data['type']);
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $service = app(LeaveAllocationService::class);
        $canTakeLeave = $service->canTakeLeave($user, $leaveType, $startDate, $endDate);

        if (!$canTakeLeave['allowed']) {
            Notification::make()
                ->title('Leave Allocation Exceeded')
                ->body($canTakeLeave['message'])
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
