<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands\Module;

use Devanox\Core\Support\Module;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Devanox\Core\Helpers\tenant;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'module:migrate', description: 'Migrate the module database')]
#[Description('Migrate the module database')]
#[Signature('module:migrate {module?} {--database= : The database connection to use} {--force : Force the operation to run when in production} {--pretend : Dump the SQL queries that would be run} {--seed : Indicates if the seed task should be re-run} {--step : Force the migrations to be run so they can be rolled back individually}')]
final class Migrate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force') && ! confirm('Application is in production! Do you wish to run module migrations?')) {
            warning('Action canceled.');

            return Command::SUCCESS;
        }

        $modules = Module::get()->where('enabled', true);

        /** @var string|null $moduleName */
        $moduleName = $this->argument('module');

        if ($moduleName) {
            $moduleName = Str::studly($moduleName);

            if (! Module::exist($moduleName)) {
                error(sprintf('Module %s does not exist!', $moduleName));

                return Command::FAILURE;
            }

            $modules = $modules->where('name', $moduleName);

            if ($modules->isEmpty()) {
                warning(sprintf('Module %s is disabled. Cannot run migrations for a disabled module.', $moduleName));

                return Command::FAILURE;
            }
        }

        if ($modules->isEmpty()) {
            warning('No enabled modules found to migrate.');

            return Command::SUCCESS;
        }

        foreach ($modules as $module) {
            info('Migrating module: ' . $module->name);

            $exitCode = $this->runMigrateCommand($module->name);

            if ($exitCode !== Command::SUCCESS) {
                error(sprintf('Migration failed for module %s.', $module->name));

                return Command::FAILURE;
            }
        }

        info('Module migrations completed successfully.');

        return Command::SUCCESS;
    }

    private function runMigrateCommand(string $module): int
    {
        $path = Module::pathFor($module, 'migrations');

        if (tenant()) {
            $path .= DIRECTORY_SEPARATOR . 'tenant';
        }

        $path = str($path)->replace(base_path(DIRECTORY_SEPARATOR), '')->__toString();

        $options = [
            '--path' => $path,
            '--force' => true,
        ];

        if ($this->option('database')) {
            $options['--database'] = $this->option('database');
        }

        if ($this->option('pretend')) {
            $options['--pretend'] = true;
        }

        if ($this->option('seed')) {
            $options['--seed'] = true;
        }

        if ($this->option('step')) {
            $options['--step'] = true;
        }

        return $this->call('migrate', $options);
    }
}
