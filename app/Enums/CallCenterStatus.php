<?php

namespace App\Enums;

enum CallCenterStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case CALLED = 'called';
    case NOT_ANSWERED = 'not_answered';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ASSIGNED => 'Assigned',
            self::CALLED => 'Called',
            self::NOT_ANSWERED => 'Not Answered',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::ASSIGNED => 'warning',
            self::CALLED => 'info',
            self::NOT_ANSWERED => 'danger',
            self::COMPLETED => 'success',
        };
    }

    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::ASSIGNED->value => self::ASSIGNED->label(),
            self::CALLED->value => self::CALLED->label(),
            self::NOT_ANSWERED->value => self::NOT_ANSWERED->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
        ];
    }

    public static function colorMap(): array
    {
        return [
            self::PENDING->value => self::PENDING->color(),
            self::ASSIGNED->value => self::ASSIGNED->color(),
            self::CALLED->value => self::CALLED->color(),
            self::NOT_ANSWERED->value => self::NOT_ANSWERED->color(),
            self::COMPLETED->value => self::COMPLETED->color(),
        ];
    }
}
