<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Console\Migrations\BaseCommand as Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[AsCommand(name: 'migrate:check')]
#[Description('Check if any migrations are pending.')]
#[Signature('migrate:check {--database= : The database connection to use.}
                {--path=* : The path(s) to the migrations files to be executed.}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths.}')]
final class MigrateCheck extends Command
{
    public function __construct(protected Migrator $migrator)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $database */
        $database = $this->option('database');

        /** @var int $result */
        $result = $this->migrator->usingConnection($database, function (): int {
            if (! $this->migrator->repositoryExists()) {
                error('Migration table not found.');

                return Command::FAILURE;
            }

            $ran = $this->migrator->getRepository()->getRan();

            $migrations = $this->getStatusFor($ran);

            if ($migrations->isNotEmpty()) {
                table(
                    ['Migration name', 'Status'],
                    $migrations->all(),
                );

                return Command::FAILURE;
            }

            info('No pending migrations found.');

            return Command::SUCCESS;
        });

        return $result;
    }

    /**
     * Get the status for the given run migrations.
     *
     * @param  array<string>  $ran
     * @return Collection<int, array{0: string, 1: string}>
     */
    private function getStatusFor(array $ran): Collection
    {
        return collect($this->getAllMigrationFiles())
            ->map(fn (string $migration): string => $this->migrator->getMigrationName($migration))
            ->reject(fn (string $migrationName): bool => in_array($migrationName, $ran))
            ->map(fn (string $migrationName): array => [$migrationName, 'Pending'])
            ->values();
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array<string, string>
     */
    private function getAllMigrationFiles(): array
    {
        $paths = $this->getMigrationPaths();

        // TODO: Handle tenant migrations after implementing multi-tenancy
        // if (tenant()) {
        //     $path = database_path('migrations');

        //     // remove $path from $paths if exists
        //     $paths = array_filter($paths, fn($p) => $p !== $path);
        //     $paths[] = database_path('migrations/tenant');
        // }

        return $this->migrator->getMigrationFiles($paths);
    }
}
