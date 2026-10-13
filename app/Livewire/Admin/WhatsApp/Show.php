<?php

namespace App\Livewire\Admin\WhatsApp;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppAssignmentService;
use App\Services\WhatsAppChatFolderService;
use App\Services\WhatsAppLeadService;
use App\Support\WhatsAppMediaStorage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public WhatsAppConversation $conversation;

    public string $reply = '';

    public $attachment = null;

    public ?int $folder_id = null;

    public function mount(): void
    {
        $this->authorizeChat();
        if ($this->conversation->isAssignedTo(auth()->user())) {
            $this->conversation->markAsRead();
        }
        $this->refreshChat();
        $this->folder_id = $this->conversation->folder_id;
    }

    private function authorizeChat(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->canViewWhatsAppConversation($this->conversation), 403);
    }

    private function refreshChat(): void
    {
        $this->conversation->load(['contact', 'lead', 'assignedUser', 'folder', 'messages.sentByUser']);
    }

    public function send(): void
    {
        $this->authorizeChat();
        abort_unless($this->conversation->isAssigned(), 422, 'Assign this conversation before replying.');
        $maxSize = (int) config('whatsapp.max_document_size_kb', 16384);

        $this->validate([
            'reply' => ['nullable', 'string', 'max:4096', 'required_without:attachment'],
            'attachment' => [
                'nullable',
                'file',
                "max:{$maxSize}",
                function (string $attribute, mixed $file, \Closure $fail): void {
                    $mimeType = (string) $file->getMimeType();
                    $extension = strtolower((string) $file->getClientOriginalExtension());

                    if (! in_array($mimeType, config('whatsapp.allowed_media_mimes', []), true)
                        && ! in_array($extension, config('whatsapp.allowed_media_extensions', []), true)) {
                        $fail('This file type cannot be sent through WhatsApp.');
                    }

                    if (str_starts_with($mimeType, 'image/')
                        && $file->getSize() > ((int) config('whatsapp.max_image_size_kb', 5120) * 1024)) {
                        $fail('WhatsApp images may not be larger than '.config('whatsapp.max_image_size_kb', 5120).' KB.');
                    }
                },
            ],
        ]);

        $body = trim($this->reply);
        $media = $this->attachment
            ? WhatsAppMediaStorage::storeUploadedFile($this->conversation->id, $this->attachment)
            : null;

        try {
            $message = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $this->conversation->id,
                'wamid' => 'local-'.Str::uuid(),
                'direction' => 'outbound',
                'type' => $media['type'] ?? 'text',
                'body' => $body !== '' ? $body : null,
                'media_mime_type' => $media['mime_type'] ?? null,
                'media_filename' => $media['filename'] ?? null,
                'media_path' => $media['path'] ?? null,
                'status' => 'pending',
                'sent_by_user_id' => auth()->id(),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            if ($media) {
                WhatsAppMediaStorage::disk()->delete($media['path']);
            }

            throw $exception;
        }

        SendWhatsAppMessageJob::dispatch($message->id);
        $preview = $message->body ?: 'Attachment: '.$message->mediaLabel();
        $this->conversation->update([
            'last_message_at' => $message->sent_at,
            'last_message_preview' => Str::limit($preview, 120),
        ]);

        $this->reset('reply', 'attachment');
        $this->refreshChat();
    }

    public function removeAttachment(): void
    {
        $this->reset('attachment');
        $this->resetValidation('attachment');
    }

    public function convert(WhatsAppLeadService $service)
    {
        $this->authorizeChat();
        abort_if($this->conversation->lead_id, 422);
        abort_unless($this->conversation->isAssigned(), 422, 'Assign this conversation before creating a lead.');
        $lead = $service->createFromConversation($this->conversation);
        session()->flash('success', 'Lead created from WhatsApp.');

        return $this->redirectRoute('admin.leads.show', $lead, navigate: true);
    }

    public function move(WhatsAppChatFolderService $service): void
    {
        $this->authorizeChat();
        $folder = $this->folder_id ? auth()->user()->whatsappChatFolders()->findOrFail($this->folder_id) : null;
        $service->moveConversation($this->conversation, auth()->user(), $folder);
        $this->refreshChat();
    }

    public function assignConversation(string $assigneeId, WhatsAppAssignmentService $service): void
    {
        $actor = auth()->user();
        abort_unless($actor->canAssignWhatsAppConversations(), 403);

        $assignee = $assigneeId === '' ? null : $this->assignmentTeam()->firstWhere('id', (int) $assigneeId);
        abort_if($assigneeId !== '' && ! $assignee, 422, 'Select a sales team member.');

        $this->conversation = $service->assign($this->conversation, $actor, $assignee);
        $this->folder_id = null;
        $this->refreshChat();
        session()->flash('success', $assignee ? "Conversation assigned to {$assignee->name}." : 'Conversation returned to the unassigned queue.');
    }

    public function claimConversation(WhatsAppAssignmentService $service): void
    {
        $actor = auth()->user();
        abort_unless($actor->canClaimWhatsAppConversations(), 403);

        $this->conversation = $service->claim($this->conversation, $actor);
        $this->conversation->markAsRead();
        $this->refreshChat();
        session()->flash('success', 'Conversation assigned to you. You can now reply or create a lead.');
    }

    private function assignmentTeam()
    {
        return User::query()->where('role', 'sales')->orderBy('name')->get();
    }

    public function render()
    {
        $canAssign = auth()->user()->canAssignWhatsAppConversations();

        return view('livewire.admin.whatsapp.show', [
            'folders' => auth()->user()->whatsappChatFolders()->orderBy('position')->get(),
            'assignmentTeam' => $canAssign ? $this->assignmentTeam() : collect(),
            'canAssign' => $canAssign,
            'canClaim' => auth()->user()->canClaimWhatsAppConversations(),
        ])->layout('components.layouts.admin', ['title' => $this->conversation->contact?->displayName() ?? 'WhatsApp chat']);
    }
}
