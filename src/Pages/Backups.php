<?php

namespace ShuvroRoy\FilamentSpatieLaravelBackup\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\Option;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\Jobs\CreateBackupJob;

class Backups extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament-spatie-backup::pages.backups';

    public function getHeading(): string | Htmlable
    {
        return __('filament-spatie-backup::backup.pages.backups.heading');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.label');
    }

    protected function getActions(): array
    {
        return [
            Action::make('Create Backup')
                ->button()
                ->label(__('filament-spatie-backup::backup.pages.backups.actions.create_backup'))
                ->action('openOptionModal'),

            Action::make('Backup all now')
                ->color('success')
                ->button()
                ->action('createNow'),
        ];
    }

    public function openOptionModal(): void
    {
        $this->dispatch('open-modal', id: 'backup-option');
    }

    public function create(string $option = ''): void
    {
        /** @var FilamentSpatieLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        CreateBackupJob::dispatch(Option::from($option), $plugin->getTimeout(), justDatabase: true)
            ->onQueue($plugin->getQueue())
            ->afterResponse();

        $this->dispatch('close-modal', id: 'backup-option');

        Notification::make()
            ->title(__('filament-spatie-backup::backup.pages.backups.messages.backup_success'))
            ->success()
            ->send();
    }

    public function createNow(string $option = ''): void
    {
        /** @var FilamentSpatieLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        CreateBackupJob::dispatch(Option::from($option), $plugin->getTimeout(), justDatabase: false)
            ->onQueue($plugin->getQueue())
            ->afterResponse();

        $this->dispatch('close-modal', id: 'backup-option');

        Notification::make()
            ->title(__('filament-spatie-backup::backup.pages.backups.messages.backup_success'))
            ->success()
            ->send();
    }

    public function shouldDisplayStatusListRecords(): bool
    {
        /** @var FilamentSpatieLaravelBackupPlugin $plugin */
        $plugin = filament()->getPlugin('filament-spatie-backup');

        return $plugin->hasStatusListRecordsTable();
    }
}
