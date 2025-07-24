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
} 