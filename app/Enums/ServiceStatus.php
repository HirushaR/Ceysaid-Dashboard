<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case PENDING = 'pending';
    case NOT_REQUIRED = 'not_required';
    case DONE = 'done';

    public static function options(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::NOT_REQUIRED->value => 'Not Required',
            self::DONE->value => 'Done',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::NOT_REQUIRED => 'Not Required',
            self::DONE => 'Done',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::NOT_REQUIRED => 'gray',
            self::DONE => 'success',
        };
    }

    public static function colorMap(): array
    {
        return [
            self::PENDING->value => self::PENDING->color(),
            self::NOT_REQUIRED->value => self::NOT_REQUIRED->color(),
            self::DONE->value => self::DONE->color(),
        ];
    }
} 