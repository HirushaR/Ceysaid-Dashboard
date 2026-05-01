<?php

namespace App\Enums;

enum OtherLeadStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Confirmed => 'warning',
            self::Completed => 'success',
        };
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
