<?php

namespace Devanox\Core\Commands;

use Devanox\Core\Helpers\EnvEditor;
use Devanox\Core\Helpers\InstallerInfo;
use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        InstallerInfo::create([
            'status' => 'migrating',
            'version' => config('app.version'),
        ]);

        $this->info('Installing the application...');

        // $this->call('key:generate');

        $this->call('storage:unlink');
        $this->call('storage:link');

        $this->call('migrate:fresh');

        // EnvEditor::setMultiple([
        //     'SESSION_DRIVER' => 'database',
        //     'CACHE_STORE' => 'database',
        //     'QUEUE_CONNECTION' => 'database',
        // ]);

        $this->call('cache:clear');

        $this->info('Application installed successfully.');

        InstallerInfo::setData('status', 'migrated');

        return Command::SUCCESS;
    }
}
