<?php

namespace App\Filament\Resources\MyWhatsAppChatResource\Pages;

use App\Filament\Resources\Concerns\OrdersWhatsAppConversationsByRecentActivity;
use App\Filament\Resources\MyWhatsAppChatResource;
use App\Models\WhatsAppChatFolder;
use App\Services\WhatsAppChatFolderService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMyWhatsAppChats extends ListRecords
{
    use OrdersWhatsAppConversationsByRecentActivity;

    protected static string $resource = MyWhatsAppChatResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
    }

    protected function getHeaderActions(): array
    {
        $folderService = app(WhatsAppChatFolderService::class);

        if (! $folderService->userCanManageFolders(auth()->user())) {
            return [];
        }

        return [
            Actions\Action::make('createFolder')
                ->label('New folder')
                ->icon('heroicon-o-folder-plus')
                ->color('primary')
                ->button()
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Folder name')
                        ->required()
                        ->maxLength(100),
                ])
                ->action(function (array $data, WhatsAppChatFolderService $folderService): void {
                    $folder = $folderService->createFolder(auth()->user(), $data['name']);

                    Notification::make()
                        ->title('Folder created')
                        ->body('Folder "'.$folder->name.'" is ready to use.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('renameFolder')
                ->label('Rename folder')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('folder_id')
                        ->label('Folder')
                        ->options(fn (): array => app(WhatsAppChatFolderService::class)->folderOptionsForUser(auth()->user()))
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('name')
                        ->label('New name')
                        ->required()
                        ->maxLength(100),
                ])
                ->action(function (array $data, WhatsAppChatFolderService $folderService): void {
                    $folder = WhatsAppChatFolder::query()->findOrFail($data['folder_id']);
                    $folder = $folderService->renameFolder($folder, auth()->user(), $data['name']);

                    Notification::make()
                        ->title('Folder renamed')
                        ->body('Folder is now called "'.$folder->name.'".')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('deleteFolder')
                ->label('Delete folder')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete folder?')
                ->modalDescription('Chats in this folder will move back to Unfiled.')
                ->form([
                    Forms\Components\Select::make('folder_id')
                        ->label('Folder')
                        ->options(fn (): array => app(WhatsAppChatFolderService::class)->folderOptionsForUser(auth()->user()))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data, WhatsAppChatFolderService $folderService): void {
                    $folder = WhatsAppChatFolder::query()->findOrFail($data['folder_id']);
                    $name = $folder->name;

                    $folderService->deleteFolder($folder, auth()->user());

                    Notification::make()
                        ->title('Folder deleted')
                        ->body('Folder "'.$name.'" was deleted. Its chats are now unfiled.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        $folderService = app(WhatsAppChatFolderService::class);

        if (! $folderService->userCanManageFolders(auth()->user())) {
            return [];
        }

        $baseQuery = fn (): Builder => MyWhatsAppChatResource::getEloquentQuery();

        $tabs = [
            'all' => Tab::make('All')
                ->badge($baseQuery()->count()),
            'unfiled' => Tab::make('Unfiled')
                ->badge($baseQuery()->whereNull('folder_id')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('folder_id')),
        ];

        $folders = auth()->user()->whatsappChatFolders;

        foreach ($folders as $folder) {
            $tabs['folder_'.$folder->id] = Tab::make($folder->name)
                ->badge($baseQuery()->where('folder_id', $folder->id)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('folder_id', $folder->id));
        }

        return $tabs;
    }
}
