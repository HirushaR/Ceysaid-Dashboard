<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank transfer',
            self::CreditCard => 'Credit card',
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
