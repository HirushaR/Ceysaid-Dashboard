<?php

namespace App\Enums;

enum LeaveType: string
{
    case ANNUAL = 'annual';
    case SICK = 'sick';
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
            self::PERSONAL => 'Personal Leave',
            self::MATERNITY => 'Maternity Leave',
            self::PATERNITY => 'Paternity Leave',
            self::EMERGENCY => 'Emergency Leave',
            self::UNPAID => 'Unpaid Leave',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
} 