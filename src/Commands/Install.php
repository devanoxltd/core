<?php

namespace Devanox\Core\Commands;

use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install {--force}';

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
        $installedFile = storage_path('installed');

        if (file_exists($installedFile) && ! $this->option('force')) {
            $this->warn('Application is already installed.');

            return Command::SUCCESS;
        }

        $this->info('Installing the application...');

        $this->call('key:generate');

        $this->call('storage:unlink');
        $this->call('storage:link');

        $this->call('migrate:fresh');

        $this->call('cache:clear');

        touch($installedFile);

        $this->info('Application installed successfully.');

        $this->info('Setup complete. You can now access your application.');

        if ($this->option('force')) {
            $this->info('Installation forced. All existing data has been overridden.');
        }

        return Command::SUCCESS;
    }
}
