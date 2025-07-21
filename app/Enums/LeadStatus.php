<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case ASSIGNED_TO_SALES = 'assigned_to_sales';
    case ASSIGNED_TO_OPERATIONS = 'assigned_to_operations';
    case INFO_GATHER_COMPLETE = 'info_gather_complete';
    case MARK_COMPLETED = 'mark_completed';
    case MARK_CLOSED = 'mark_closed';
    case PRICING_IN_PROGRESS = 'pricing_in_progress';
    case SENT_TO_CUSTOMER = 'sent_to_customer';
    case OPERATION_COMPLETE = 'operation_complete'; // found in MyOperationLeadDashboardResource

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::ASSIGNED_TO_SALES => 'Assigned to Sales',
            self::ASSIGNED_TO_OPERATIONS => 'Assigned to Operations',
            self::INFO_GATHER_COMPLETE => 'Info Gather Complete',
            self::MARK_COMPLETED => 'Mark Completed',
            self::MARK_CLOSED => 'Mark Closed',
            self::PRICING_IN_PROGRESS => 'Pricing In Progress',
            self::SENT_TO_CUSTOMER => 'Sent to Customer',
            self::OPERATION_COMPLETE => 'Operation Complete',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => 'gray',
            self::ASSIGNED_TO_SALES => 'info',
            self::ASSIGNED_TO_OPERATIONS => 'warning',
            self::INFO_GATHER_COMPLETE => 'success',
            self::MARK_COMPLETED => 'success',
            self::MARK_CLOSED => 'danger',
            self::PRICING_IN_PROGRESS => 'primary',
            self::SENT_TO_CUSTOMER => 'success',
            self::OPERATION_COMPLETE => 'success',
        };
    }

    public static function options(): array
    {
        return [
            self::NEW->value => self::NEW->label(),
            self::ASSIGNED_TO_SALES->value => self::ASSIGNED_TO_SALES->label(),
            self::ASSIGNED_TO_OPERATIONS->value => self::ASSIGNED_TO_OPERATIONS->label(),
            self::INFO_GATHER_COMPLETE->value => self::INFO_GATHER_COMPLETE->label(),
            self::MARK_COMPLETED->value => self::MARK_COMPLETED->label(),
            self::MARK_CLOSED->value => self::MARK_CLOSED->label(),
            self::PRICING_IN_PROGRESS->value => self::PRICING_IN_PROGRESS->label(),
            self::SENT_TO_CUSTOMER->value => self::SENT_TO_CUSTOMER->label(),
            self::OPERATION_COMPLETE->value => self::OPERATION_COMPLETE->label(),
        ];
    }

    public static function colorMap(): array
    {
        return [
            self::NEW->value => self::NEW->color(),
            self::ASSIGNED_TO_SALES->value => self::ASSIGNED_TO_SALES->color(),
            self::ASSIGNED_TO_OPERATIONS->value => self::ASSIGNED_TO_OPERATIONS->color(),
            self::INFO_GATHER_COMPLETE->value => self::INFO_GATHER_COMPLETE->color(),
            self::MARK_COMPLETED->value => self::MARK_COMPLETED->color(),
            self::MARK_CLOSED->value => self::MARK_CLOSED->color(),
            self::PRICING_IN_PROGRESS->value => self::PRICING_IN_PROGRESS->color(),
            self::SENT_TO_CUSTOMER->value => self::SENT_TO_CUSTOMER->color(),
            self::OPERATION_COMPLETE->value => self::OPERATION_COMPLETE->color(),
        ];
    }
} 