<?php

namespace Devanox\Core\Commands\Module;

use Devanox\Core\Support\Module;
use Illuminate\Console\Command;

class Migrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate {module?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate the module database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = Module::get()->where('enabled', true);

        $module = $this->argument('module');

        if ($module) {
            $modules = $modules->where('name', $module);
        }

        if ($modules->isEmpty()) {
            $this->error('No modules found');
            return Command::FAILURE;
        }

        $modules->each(function ($module) {
            $this->info('Migrating module: ' . $module->name);

            $this->runMigrateCommand($module->name);
        });

        return Command::SUCCESS;
    }

    protected function runMigrateCommand($module)
    {

        $path = Module::pathFor($module, 'migrations');

        if (tenant()) {
            $path .= DIRECTORY_SEPARATOR . 'tenant';
        }

        $path = str($path)->replace(base_path(DIRECTORY_SEPARATOR), '')->__toString();

        $this->call('migrate', [
            '--path' => $path,
            '--force' => true,
        ]);
    }
}
