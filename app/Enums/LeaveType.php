<?php

namespace App\Enums;

enum LeaveType: string
{
    case ANNUAL = 'annual';
    case SICK = 'sick';
    case CASUAL = 'casual';
    case PERSONAL = 'personal';
    case MATERNITY = 'maternity';
    case PATERNITY = 'paternity';
    case EMERGENCY = 'emergency';
    case UNPAID = 'unpaid';

    public function getLabel(): string
    {
        return match ($this) {
            self::ANNUAL => 'Annual Leave',
            self::SICK => 'Sick Leave',
            self::CASUAL => 'Casual Leave',
            self::PERSONAL => 'Personal Leave',
            self::MATERNITY => 'Maternity Leave',
            self::PATERNITY => 'Paternity Leave',
            self::EMERGENCY => 'Emergency Leave',
            self::UNPAID => 'Unpaid Leave',
        };
    }

    /**
     * Get the annual allocation for this leave type (days per calendar year)
     */
    public function getAllocation(): int
    {
        return match ($this) {
            self::ANNUAL => 14,
            self::SICK => 7,
            self::CASUAL => 7,
            self::PERSONAL => 0, // Not part of standard allocation
            self::MATERNITY => 0, // Special leave, not counted in standard allocation
            self::PATERNITY => 0, // Special leave, not counted in standard allocation
            self::EMERGENCY => 0, // Not part of standard allocation
            self::UNPAID => 0, // Unpaid, not counted
        };
    }

    /**
     * Check if this leave type counts towards the 28-day annual limit
     */
    public function countsTowardsAnnualLimit(): bool
    {
        return in_array($this, [self::ANNUAL, self::SICK, self::CASUAL]);
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
} 