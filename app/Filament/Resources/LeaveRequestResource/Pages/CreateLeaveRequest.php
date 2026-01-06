<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use App\Services\LeaveAllocationService;
use App\Models\User;
use App\Enums\LeaveType;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure the current user is set as the owner
        $data['user_id'] = auth()->id();
        $data['created_by'] = auth()->id();
        
        // Validate leave allocation limits
        $user = User::find(auth()->id());
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

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Leave Request Submitted')
            ->body('Your leave request has been submitted successfully and is pending approval.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
