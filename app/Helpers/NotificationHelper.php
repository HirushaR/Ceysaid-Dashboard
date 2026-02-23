<?php

namespace App\Helpers;

use App\Models\LeadNote;
use Illuminate\Support\Facades\Auth;

class NotificationHelper
{
    /**
     * Get unread internal note count for current user (sales/operation only).
     * Counts notes on leads where user is assigned_to (sales) or assigned_operator (operation)
     * and the user has not read the note.
     */
    public static function getInternalNoteUnreadCount(): int
    {
        $user = Auth::user();
        if (!$user || (! $user->isSales() && ! $user->isOperation())) {
            return 0;
        }

        $userId = $user->id;

        return LeadNote::query()
            ->whereHas('lead', function ($q) use ($userId) {
                $q->whereNull('archived_at')
                    ->where(function ($q2) use ($userId) {
                        $q2->where('assigned_to', $userId)
                            ->orWhere('assigned_operator', $userId);
                    });
            })
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))
            ->count();
    }
    /**
     * Get unread notification count for current user
     */
    public static function getUnreadCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    /**
     * Get unread notification count for lead-related notifications
     */
    public static function getLeadNotificationCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $type = $data['type'] ?? '';
                return in_array($type, ['lead_assignment', 'lead_status_change', 'new_lead', 'service_status_change']);
            })
            ->count();
    }

    /**
     * Get unread notification count for confirmed leads
     */
    public static function getConfirmLeadNotificationCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $type = $data['type'] ?? '';
                $newValue = $data['new_value'] ?? '';
                return $type === 'lead_status_change' && 
                       in_array($newValue, ['confirmed', 'document_upload_complete']);
            })
            ->count();
    }

    /**
     * Get unread notification count for visa-related notifications
     */
    public static function getVisaLeadNotificationCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $type = $data['type'] ?? '';
                $service = $data['service'] ?? '';
                $newValue = $data['new_value'] ?? '';
                return ($type === 'service_status_change' && $service === 'visa_status') ||
                       ($type === 'lead_status_change' && in_array($newValue, ['confirmed', 'document_upload_complete']));
            })
            ->count();
    }

    /**
     * Get unread notification count for sales-related notifications
     */
    public static function getSalesNotificationCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $type = $data['type'] ?? '';
                return in_array($type, ['lead_assignment', 'new_lead']);
            })
            ->count();
    }

    /**
     * Get unread notification count for operation-related notifications
     */
    public static function getOperationNotificationCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $type = $data['type'] ?? '';
                $service = $data['service'] ?? '';
                return $type === 'service_status_change' || 
                       ($type === 'lead_assignment' && isset($data['assigned_operator']));
            })
            ->count();
    }
}
