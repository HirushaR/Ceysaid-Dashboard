<?php

namespace App\Enums;

enum ClosureType: string
{
    case HOLIDAY = 'holiday';
    case OFFICE_CLOSURE = 'office_closure';

    public function getLabel(): string
    {
        return match ($this) {
            self::HOLIDAY => 'Holiday',
            self::OFFICE_CLOSURE => 'Office Closure',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}




