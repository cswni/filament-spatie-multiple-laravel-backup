<?php

namespace ShuvroRoy\FilamentSpatieLaravelBackup\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\Option;
use Spatie\Backup\Commands\BackupCommand;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected readonly Option $option = Option::ALL,
        protected readonly ?int $timeout = null,
    ) {
        $this->onQueue('default');
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        // @TODO
        //Load from database the site configuration

        // Mock site configuration
        $site = [
            'host' => 'mariadb_shaddai',
            'port' => '3306',
            'database' => 'shaddai',
            'username' => 'cswni',
            'password' => 'cswni',
        ];

        $this->setupConfigForSite($site);

        Artisan::call(BackupCommand::class, [
            '--only-db' => 1,
            '--only-files' => 0,
            '--filename' => $site ['database'] .'-'.date('Y-m-d-H-i-s') . '.zip',
            '--timeout' => $this->timeout,
        ]);
    }

    public function setupConfigForSite($site): void
    {

        try {
            //Clear the config
            Artisan::call('config:clear');

            Config::set('backup.backup.name', 'demo');
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
}
