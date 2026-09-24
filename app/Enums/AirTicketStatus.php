<?php

namespace App\Enums;

enum AirTicketStatus: string
{
    case PENDING_APPROVAL = 'pending_approval';
    case RETURNED_FOR_CORRECTION = 'returned_for_correction';
    case REJECTED = 'rejected';
    case READY_FOR_TICKETING = 'ready_for_ticketing';
    case ON_HOLD = 'on_hold';
    case ISSUED = 'issued';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL => 'Pending Accounts Approval',
            self::RETURNED_FOR_CORRECTION => 'Returned for Correction',
            self::REJECTED => 'Rejected',
            self::READY_FOR_TICKETING => 'Ready for Ticketing',
            self::ON_HOLD => 'On Hold',
            self::ISSUED => 'Ticket Issued',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL => 'status-warning',
            self::RETURNED_FOR_CORRECTION, self::REJECTED => 'status-danger',
            self::READY_FOR_TICKETING => 'status-violet',
            self::ON_HOLD => 'status-neutral',
            self::ISSUED => 'status-success',
        };
    }
}
