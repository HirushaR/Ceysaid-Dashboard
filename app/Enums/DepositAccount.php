<?php

namespace App\Enums;

enum DepositAccount: string
{
    case Cash = 'cash';
    case NtbCurrent = 'ntb_current';
    case NtbSaving = 'ntb_saving';
    case SeylanSaving = 'seylan_saving';
    case SeylanCurrent = 'seylan_current';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::NtbCurrent => 'NTB Current',
            self::NtbSaving => 'NTB Saving',
            self::SeylanSaving => 'Seylan Saving',
            self::SeylanCurrent => 'Seylan Current',
        };
    }

    public static function options(): array
    {
        $o = [];
        foreach (self::cases() as $case) {
            $o[$case->value] = $case->label();
        }

        return $o;
    }
}
