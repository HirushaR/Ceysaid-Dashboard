<?php

namespace App\Enums;

enum Platform: string
{
    case FACEBOOK = 'facebook';
    case WHATSAPP = 'whatsapp';
    case EMAIL = 'email';

    public function label(): string
    {
        return match($this) {
            self::FACEBOOK => 'Facebook',
            self::WHATSAPP => 'WhatsApp',
            self::EMAIL => 'Email',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::FACEBOOK => 'info',
            self::WHATSAPP => 'success',
            self::EMAIL => 'warning',
        };
    }

    public static function options(): array
    {
        return [
            self::FACEBOOK->value => self::FACEBOOK->label(),
            self::WHATSAPP->value => self::WHATSAPP->label(),
            self::EMAIL->value => self::EMAIL->label(),
        ];
    }

    public static function colorMap(): array
    {
        return [
            self::FACEBOOK->value => self::FACEBOOK->color(),
            self::WHATSAPP->value => self::WHATSAPP->color(),
            self::EMAIL->value => self::EMAIL->color(),
        ];
    }
} 