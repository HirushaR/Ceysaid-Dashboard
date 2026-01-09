<?php

namespace App\Enums;

enum Platform: string
{
    case FACEBOOK = 'facebook';
    case TIKTOK = 'tiktok';
    case WHATSAPP = 'whatsapp';
    case EMAIL = 'email';
    case HOTLINE = 'hotline';
    case TV = 'tv';
    case RETAINER = 'retainer';
    case SUGGESTION = 'suggestion';

    public function label(): string
    {
        return match($this) {
            self::FACEBOOK => 'Facebook',
            self::TIKTOK => 'TikTok',
            self::WHATSAPP => 'WhatsApp',
            self::EMAIL => 'Email',
            self::HOTLINE => 'Hotline',
            self::TV => 'TV',
            self::RETAINER => 'Retainer',
            self::SUGGESTION => 'Suggestion',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::FACEBOOK => 'info',
            self::TIKTOK => 'danger',
            self::WHATSAPP => 'success',
            self::EMAIL => 'warning',
            self::HOTLINE => 'primary',
            self::TV => 'info',
            self::RETAINER => 'success',
            self::SUGGESTION => 'gray',
        };
    }

    public static function options(): array
    {
        return [
            self::FACEBOOK->value => self::FACEBOOK->label(),
            self::TIKTOK->value => self::TIKTOK->label(),
            self::WHATSAPP->value => self::WHATSAPP->label(),
            self::EMAIL->value => self::EMAIL->label(),
            self::HOTLINE->value => self::HOTLINE->label(),
            self::TV->value => self::TV->label(),
            self::RETAINER->value => self::RETAINER->label(),
            self::SUGGESTION->value => self::SUGGESTION->label(),
        ];
    }

    public static function colorMap(): array
    {
        return [
            self::FACEBOOK->value => self::FACEBOOK->color(),
            self::TIKTOK->value => self::TIKTOK->color(),
            self::WHATSAPP->value => self::WHATSAPP->color(),
            self::EMAIL->value => self::EMAIL->color(),
            self::HOTLINE->value => self::HOTLINE->color(),
            self::TV->value => self::TV->color(),
            self::RETAINER->value => self::RETAINER->color(),
            self::SUGGESTION->value => self::SUGGESTION->color(),
        ];
    }
} 