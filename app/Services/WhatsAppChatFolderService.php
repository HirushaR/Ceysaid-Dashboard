<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsAppChatFolder;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WhatsAppChatFolderService
{
    public function createFolder(User $user, string $name): WhatsAppChatFolder
    {
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Folder name is required.',
            ]);
        }

        $position = (int) $user->whatsappChatFolders()->max('position') + 1;

        return $user->whatsappChatFolders()->create([
            'name' => $name,
            'position' => $position,
        ]);
    }

    public function renameFolder(WhatsAppChatFolder $folder, User $user, string $name): WhatsAppChatFolder
    {
        $this->assertFolderOwner($folder, $user);

        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Folder name is required.',
            ]);
        }

        $folder->update(['name' => $name]);

        return $folder->fresh();
    }

    public function deleteFolder(WhatsAppChatFolder $folder, User $user): void
    {
        $this->assertFolderOwner($folder, $user);

        DB::transaction(function () use ($folder): void {
            $folder->conversations()->update(['folder_id' => null]);
            $folder->delete();
        });
    }

    public function moveConversation(WhatsAppConversation $conversation, User $user, ?WhatsAppChatFolder $folder): void
    {
        $this->assertCanOrganizeConversation($conversation, $user);

        if ($folder) {
            $this->assertFolderOwner($folder, $user);

            if (! $user->isAdmin()) {
                $this->assertFolderMatchesAssignee($conversation, $folder);
            }
        }

        $conversation->update(['folder_id' => $folder?->id]);
    }

    public function userCanManageFolders(?User $user): bool
    {
        return $user && ($user->isSales() || $user->isAdmin());
    }

    public function folderOptionsForUser(User $user): array
    {
        return $user->whatsappChatFolders()
            ->orderBy('position')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function assertCanOrganizeConversation(WhatsAppConversation $conversation, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (! $conversation->isAssignedTo($user)) {
            throw ValidationException::withMessages([
                'folder_id' => 'You can only organize chats assigned to you.',
            ]);
        }
    }

    private function assertFolderOwner(WhatsAppChatFolder $folder, User $user): void
    {
        if ($folder->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'folder_id' => 'You do not have access to this folder.',
            ]);
        }
    }

    private function assertFolderMatchesAssignee(WhatsAppConversation $conversation, WhatsAppChatFolder $folder): void
    {
        if ($conversation->assigned_to !== $folder->user_id) {
            throw ValidationException::withMessages([
                'folder_id' => 'This folder does not belong to the chat assignee.',
            ]);
        }
    }
}
