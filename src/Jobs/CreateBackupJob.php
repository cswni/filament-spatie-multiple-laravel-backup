<?php

namespace ShuvroRoy\FilamentSpatieLaravelBackup\Jobs;

use App\Models\Database;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\Option;
use Spatie\Backup\Commands\BackupCommand;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly Option | string $option = Option::ALL,
        protected readonly ?int $timeout = null,
        protected readonly ?Database $database = null,
        protected bool $justDatabase = false
    ) {
        $this->onQueue('default');
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        // If database is not null, backup only the database
        if ($this->database) {
            $this->backupDatabase($this->generateFilename($this->database));

            return;
        }

        // https://tim.macdonald.au/backup-multiple-sites-frameworks-laravel-backup/

        //Load from database the site configuration
        // Verify if exists the class Database in App\Models\Database
        if (class_exists('App\Models\Database') && ! $this->justDatabase) {
            $databases = \App\Models\Database::all();
            foreach ($databases as $database) {
                $site = $this->generateFileName($database);

                // Backup
                $this->backupDatabase($site);

                // Update last_backup_at, backup_count, last_backup_path
                $database->last_backup_at = now();
                $database->backup_count = $database->backup_count + 1;
                $database->last_backup_path = $site['filename'];
                $database->save();
            }
        }

        // If justDatabase is true, backup only the database for self site
        if ($this->justDatabase) {
            Artisan::call(BackupCommand::class, [
                '--only-db' => 1,
                '--only-files' => 0,
                '--filename' => 'databases-' . date('Y-m-d-H-i-s') . '.zip',
                '--timeout' => $this->timeout,
            ]);
        }
    }

    /**
     * @throws Exception
     */
    private function backupDatabase($site): void
    {
        $this->setupConfigForSite($site);

        Artisan::call(BackupCommand::class, [
            '--only-db' => 1,
            '--only-files' => 0,
            '--filename' => $site['filename'],
            '--timeout' => $this->timeout,
        ]);
    }

    public function setupConfigForSite($site): void
    {
        try {
            //Clear the config
            Artisan::call('config:clear');

            Config::set('backup.backup.name', 'backup-' . $site['name']);
            Config::set('database.connections.mysql.host', $site['host']);
            Config::set('database.connections.mysql.port', $site['port']);
            Config::set('database.connections.mysql.database', $site['database']);
            Config::set('database.connections.mysql.username', $site['username']);
            Config::set('database.connections.mysql.password', $site['password']);

            // Not include files; only database
            Config::set('backup.backup.source.files.include', []);

            //Filename
            Config::set('backup.backup.destination.filename_prefix', $site['database'] . '_');
        } catch (Exception $e) {
            throw new Exception('Error setting up config for site');
        }

    }


    private function generateFilename($database): array
    {
        $filename = Str::replace(' ', '-', $database['name']) . '-' . date('Y-m-d-H-i-s') . '.zip';
        return [
            'filename' => $filename,
            'name' => $database->name,
            'host' => $database->host,
            'port' => $database->port,
            'database' => $database->database,
            'username' => $database->username,
            'password' => $database->password,
        ];
    }
}
