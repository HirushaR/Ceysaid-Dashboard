<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Notifications\WhatsAppConversationNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class WhatsAppAssignmentService
{
    public function __construct(private AuditService $auditService) {}

    public function assign(WhatsAppConversation $conversation, User $actor, ?User $assignee): WhatsAppConversation
    {
        if (! $actor->canAssignWhatsAppConversations()) {
            throw new AuthorizationException('You cannot assign WhatsApp conversations.');
        }

        if ($assignee && ! $assignee->isSales()) {
            throw ValidationException::withMessages([
                'assignee' => 'Select a sales team member.',
            ]);
        }

        $previousAssignee = $conversation->assigned_to;
        $conversation->update([
            'assigned_to' => $assignee?->id,
            'assigned_at' => $assignee ? now() : null,
            'folder_id' => null,
        ]);

        $this->auditService->record(
            'WhatsAppConversation.assigned',
            $conversation,
            ['assigned_to' => $previousAssignee],
            ['assigned_to' => $assignee?->id],
        );

        if ($assignee && $assignee->id !== $previousAssignee && $assignee->id !== $actor->id) {
            $assignee->notify(new WhatsAppConversationNotification($conversation, 'assigned'));
        }

        return $conversation->fresh(['contact', 'assignedUser']);
    }

    public function claim(WhatsAppConversation $conversation, User $actor): WhatsAppConversation
    {
        if (! $actor->canClaimWhatsAppConversations()) {
            throw new AuthorizationException('You cannot claim WhatsApp conversations.');
        }

        $claimed = WhatsAppConversation::query()
            ->whereKey($conversation->id)
            ->whereNull('assigned_to')
            ->update([
                'assigned_to' => $actor->id,
                'assigned_at' => now(),
                'folder_id' => null,
            ]);

        if ($claimed === 0) {
            throw ValidationException::withMessages([
                'conversation' => 'This conversation has already been assigned to another salesperson.',
            ]);
        }

        $conversation->refresh();
        $this->auditService->record(
            'WhatsAppConversation.claimed',
            $conversation,
            ['assigned_to' => null],
            ['assigned_to' => $actor->id],
        );

        return $conversation->load(['contact', 'assignedUser']);
    }
}
