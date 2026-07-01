<?php

namespace App\Filament\Resources\Concerns;

use App\Models\WhatsAppChatFolder;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppChatFolderService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

trait ManagesWhatsAppChatFolders
{
    protected static function folderColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('folder.name')
            ->label('Folder')
            ->placeholder('Unfiled')
            ->toggleable();
    }

    protected static function moveToFolderAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('moveToFolder')
            ->label('Move to folder')
            ->icon('heroicon-o-folder-arrow-down')
            ->visible(fn (): bool => static::currentUserCanManageFolders())
            ->form(fn (): array => static::folderSelectFormFields())
            ->fillForm(fn (WhatsAppConversation $record): array => [
                'folder_id' => $record->folder_id,
            ])
            ->action(function (WhatsAppConversation $record, array $data, WhatsAppChatFolderService $folderService): void {
                $user = auth()->user();
                $folder = isset($data['folder_id']) && $data['folder_id']
                    ? WhatsAppChatFolder::query()->find($data['folder_id'])
                    : null;

                $folderService->moveConversation($record, $user, $folder);

                Notification::make()
                    ->title($folder ? 'Chat moved to folder' : 'Chat removed from folder')
                    ->success()
                    ->send();
            });
    }

    protected static function moveToFolderBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('moveToFolder')
            ->label('Move to folder')
            ->icon('heroicon-o-folder-arrow-down')
            ->visible(fn (): bool => static::currentUserCanManageFolders())
            ->form(fn (): array => static::folderSelectFormFields(includeUnfiled: false))
            ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, WhatsAppChatFolderService $folderService): void {
                $user = auth()->user();
                $folder = WhatsAppChatFolder::query()->findOrFail($data['folder_id']);

                foreach ($records as $record) {
                    $folderService->moveConversation($record, $user, $folder);
                }

                Notification::make()
                    ->title('Chats moved to folder')
                    ->body($records->count().' chat(s) moved to '.$folder->name.'.')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function folderSelectFormFields(bool $includeUnfiled = true): array
    {
        $user = auth()->user();

        $options = $user
            ? app(WhatsAppChatFolderService::class)->folderOptionsForUser($user)
            : [];

        $select = Forms\Components\Select::make('folder_id')
            ->label('Folder')
            ->options($options)
            ->searchable()
            ->required(! $includeUnfiled);

        if ($includeUnfiled) {
            $select
                ->placeholder('Unfiled')
                ->nullable();
        }

        return [$select];
    }

    protected static function currentUserCanManageFolders(): bool
    {
        return app(WhatsAppChatFolderService::class)->userCanManageFolders(auth()->user());
    }

    protected static function folderTabsBaseQuery(): Builder
    {
        return static::getEloquentQuery();
    }
}
