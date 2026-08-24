<?php

namespace App\Enums;

enum TourStatus: string
{
    case Open = 'open';
    case SoldOut = 'sold_out';
    case Departed = 'departed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::SoldOut => 'Sold Out',
            self::Departed => 'Departed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
