<?php

namespace App\Services;

use App\Models\User;
use App\Models\Leave;
use App\Enums\LeaveType;
use App\Enums\LeaveStatus;
use Carbon\Carbon;

class LeaveAllocationService
{
    /**
     * Get annual leave allocations for a user
     * 
     * @return array ['casual' => 7, 'sick' => 7, 'annual' => 14, 'total' => 28]
     */
    public function getAllocations(): array
    {
        return [
            'casual' => LeaveType::CASUAL->getAllocation(),
            'sick' => LeaveType::SICK->getAllocation(),
            'annual' => LeaveType::ANNUAL->getAllocation(),
            'total' => 28,
        ];
    }

    /**
     * Get used leave days for a user in a given calendar year
     * Only counts approved leaves that count towards the annual limit
     * 
     * @param User $user
     * @param int|null $year Calendar year (defaults to current year)
     * @return array ['casual' => 0, 'sick' => 0, 'annual' => 0, 'total' => 0]
     */
    public function getUsedLeaves(User $user, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

        $leaves = Leave::where('user_id', $user->id)
            ->where('status', LeaveStatus::APPROVED)
            ->where(function ($query) use ($startOfYear, $endOfYear) {
                $query->whereBetween('start_date', [$startOfYear, $endOfYear])
                    ->orWhereBetween('end_date', [$startOfYear, $endOfYear])
                    ->orWhere(function ($q) use ($startOfYear, $endOfYear) {
                        $q->where('start_date', '<=', $startOfYear)
                          ->where('end_date', '>=', $endOfYear);
                    });
            })
            ->whereIn('type', [
                LeaveType::CASUAL->value,
                LeaveType::SICK->value,
                LeaveType::ANNUAL->value,
            ])
            ->get();

        $used = [
            'casual' => 0,
            'sick' => 0,
            'annual' => 0,
            'total' => 0,
        ];

        foreach ($leaves as $leave) {
            // Calculate days within the calendar year
            $startDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);
            
            // Clamp dates to the calendar year
            $actualStart = $startDate->isBefore($startOfYear) ? $startOfYear : $startDate;
            $actualEnd = $endDate->isAfter($endOfYear) ? $endOfYear : $endDate;
            
            $days = $actualStart->diffInDays($actualEnd) + 1;
            
            $type = $leave->type->value;
            if (isset($used[$type])) {
                $used[$type] += $days;
                $used['total'] += $days;
            }
        }

        return $used;
    }

    /**
     * Get remaining leave days for a user in a given calendar year
     * 
     * @param User $user
     * @param int|null $year Calendar year (defaults to current year)
     * @return array ['casual' => 7, 'sick' => 7, 'annual' => 14, 'total' => 28]
     */
    public function getRemainingLeaves(User $user, ?int $year = null): array
    {
        $allocations = $this->getAllocations();
        $used = $this->getUsedLeaves($user, $year);

        return [
            'casual' => max(0, $allocations['casual'] - $used['casual']),
            'sick' => max(0, $allocations['sick'] - $used['sick']),
            'annual' => max(0, $allocations['annual'] - $used['annual']),
            'total' => max(0, $allocations['total'] - $used['total']),
        ];
    }

    /**
     * Check if a user can take a leave request without exceeding limits
     * 
     * @param User $user
     * @param LeaveType $leaveType
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $excludeLeaveId Leave ID to exclude from calculation (for updates)
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function canTakeLeave(
        User $user,
        LeaveType $leaveType,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeLeaveId = null
    ): array {
        // Only check limits for leave types that count towards annual limit
        if (!$leaveType->countsTowardsAnnualLimit()) {
            return ['allowed' => true, 'message' => ''];
        }

        $year = $startDate->year;
        $days = $startDate->diffInDays($endDate) + 1;
        
        // Get all approved/pending leaves for the calendar year (excluding the current leave if updating)
        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();
        
        $existingLeaves = Leave::where('user_id', $user->id)
            ->where('id', '!=', $excludeLeaveId)
            ->whereIn('status', [LeaveStatus::PENDING, LeaveStatus::APPROVED])
            ->where(function ($query) use ($startOfYear, $endOfYear) {
                $query->whereBetween('start_date', [$startOfYear, $endOfYear])
                    ->orWhereBetween('end_date', [$startOfYear, $endOfYear])
                    ->orWhere(function ($q) use ($startOfYear, $endOfYear) {
                        $q->where('start_date', '<=', $startOfYear)
                          ->where('end_date', '>=', $endOfYear);
                    });
            })
            ->whereIn('type', [
                LeaveType::CASUAL->value,
                LeaveType::SICK->value,
                LeaveType::ANNUAL->value,
            ])
            ->get();

        // Calculate used days by type and total
        $usedByType = ['casual' => 0, 'sick' => 0, 'annual' => 0, 'total' => 0];
        
        foreach ($existingLeaves as $existingLeave) {
            $existingStart = Carbon::parse($existingLeave->start_date);
            $existingEnd = Carbon::parse($existingLeave->end_date);
            
            // Clamp dates to the calendar year
            $actualStart = $existingStart->isBefore($startOfYear) ? $startOfYear : $existingStart;
            $actualEnd = $existingEnd->isAfter($endOfYear) ? $endOfYear : $existingEnd;
            
            $existingDays = $actualStart->diffInDays($actualEnd) + 1;
            $type = $existingLeave->type->value;
            
            if (isset($usedByType[$type])) {
                $usedByType[$type] += $existingDays;
                $usedByType['total'] += $existingDays;
            }
        }

        // Calculate what the new totals would be
        $typeKey = $leaveType->value;
        $newUsedByType = $usedByType;
        $newUsedByType[$typeKey] += $days;
        $newUsedByType['total'] += $days;

        // Get allocations
        $allocations = $this->getAllocations();

        // Check type-specific limit
        $typeAllocation = $allocations[$typeKey] ?? 0;
        if ($newUsedByType[$typeKey] > $typeAllocation) {
            $remaining = $typeAllocation - $usedByType[$typeKey];
            return [
                'allowed' => false,
                'message' => "Insufficient {$leaveType->getLabel()} balance. Remaining: {$remaining} days, Requested: {$days} days."
            ];
        }

        // Check total limit (28 days)
        if ($newUsedByType['total'] > $allocations['total']) {
            $totalRemaining = $allocations['total'] - $usedByType['total'];
            return [
                'allowed' => false,
                'message' => "Total leave days would exceed annual limit of 28 days. Remaining: {$totalRemaining} days, Requested: {$days} days."
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }
}

