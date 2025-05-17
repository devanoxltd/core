<?php

namespace Devanox\Core\Commands;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Database\Console\Migrations\BaseCommand;

#[AsCommand(name: 'migrate:check')]
class MigrateCheck extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:check {--database= : The database connection to use.}
                {--path= : The path of migrations files to be executed.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check is any migration is pending';

    protected Migrator $migrator;

    public function __construct()
    {
        parent::__construct();

        $this->migrator = app(Migrator::class);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->migrator->usingConnection($this->option('database'), function () {
            if (! $this->migrator->repositoryExists()) {
                $this->components->error('Migration table not found.');

                return BaseCommand::FAILURE;
            }

            $ran = $this->migrator->getRepository()->getRan();

            $migrations = $this->getStatusFor($ran);

            if ($migrations->isNotEmpty()) {
                $this->newLine();

                $this->components->twoColumnDetail('<fg=gray>Migration name</>', '<fg=gray>Status</>');

                $migrations
                    ->each(
                        fn($migration) => $this->components->twoColumnDetail($migration[0], $migration[1])
                    );

                $this->newLine();

                return BaseCommand::FAILURE;
            } else {
                $this->components->info('No migrations found');
            }

            return BaseCommand::SUCCESS;
        });
    }

    /**
     * Get the status for the given run migrations.
     *
     * @param  array  $ran
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran)
    {
        return (new Collection($this->getAllMigrationFiles()))
            ->map(function ($migration) use ($ran) {
                $migrationName = $this->migrator->getMigrationName($migration);

                return ! in_array($migrationName, $ran)
                    ? [$migrationName, '<fg=yellow;options=bold>Pending</>']
                    : null;
            })
            ->filter();
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        $paths = $this->getMigrationPaths();

        if (tenant()) {
            $path = database_path('migrations');

            // remove $path from $paths if exists
            $paths = array_filter($paths, fn($p) => $p !== $path);
            $paths[] = database_path('migrations/tenant');
        }

        return $this->migrator->getMigrationFiles($paths);
    }
}
