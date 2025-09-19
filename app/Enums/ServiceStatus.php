<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public static function options(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::IN_PROGRESS->value => 'In Progress',
            self::DONE->value => 'Done',
            self::CANCELLED->value => 'Cancelled',
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::DONE => 'Done',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::IN_PROGRESS => 'warning',
            self::DONE => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public static function colorMap(): array
    {
        return [
            self::PENDING->value => self::PENDING->color(),
            self::IN_PROGRESS->value => self::IN_PROGRESS->color(),
            self::DONE->value => self::DONE->color(),
            self::CANCELLED->value => self::CANCELLED->color(),
        ];
    }
} 