<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Events\Tenant\DatabaseCreated;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Description('Create a new database for the tenant')]
#[Signature('tenant:create-database {id}')]
final class TenantCreateDatabaseCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id');

        if (is_array($id) || $id === null) {
            $this->error('Invalid tenant ID provided.');

            return Command::FAILURE;
        }

        /** @var class-string<TenantContract&Model> $tenantClass */
        $tenantClass = config('tenancy.models.tenant', \Devanox\Core\Models\Tenant::class);

        /** @var Builder<TenantContract&Model> $query */
        $query = $tenantClass::query();

        $tenant = $query->find($id);

        if (! $tenant instanceof TenantContract) {
            $this->error(__('core::tenancy.commands.error.tenant_not_found', ['id' => $id]));

            return Command::FAILURE;
        }

        /** @var string $databaseHost */
        $databaseHost = config('database.connections.mysql.host', '127.0.0.1');

        /** @var string $databasePort */
        $databasePort = config('database.connections.mysql.port', '3306');

        /** @var string $databaseUsername */
        $databaseUsername = config('database.connections.mysql.username', 'root');

        /** @var string $databasePassword */
        $databasePassword = config('database.connections.mysql.password', '');

        /** @var string $prefix */
        $prefix = config('tenancy.database.prefix', '');

        /** @var string $suffix */
        $suffix = config('tenancy.database.suffix', '');

        /** @var int|string $tenantKey */
        $tenantKey = $tenant->getKey();
        $database = $prefix . $tenantKey . $suffix;
        $databasePassword = $databasePassword !== '' ? $databasePassword : null;

        $database = $this->getUniqueDatabaseName($database);

        // DDL identifiers cannot be parameterized; backtick-escape them instead.
        DB::statement('CREATE DATABASE `' . str_replace('`', '', $database) . '`');

        $this->info(__('core::tenancy.commands.database.created', ['database' => $database]));

        if ($databaseUsername !== 'root') {
            if (! $this->databaseUserExists($databaseUsername)) {
                $this->createDatabaseUser($databaseUsername, $databasePassword);

                $this->info(__('core::tenancy.commands.database.user_created', ['username' => $databaseUsername]));
            }

            $this->grantPermissions($database, $databaseUsername);

            $this->info(__('core::tenancy.commands.database.permissions_granted', ['database' => $database]));

            $this->flushPrivileges();

            $this->info(__('core::tenancy.commands.database.privileges_flushed'));
        }

        $this->info(__('core::tenancy.commands.database.completed', ['database' => $database]));

        $existingConfig = collect($tenant->config)->toArray();

        $tenant->config = array_merge($existingConfig, [
            'database' => [
                'host' => $databaseHost,
                'port' => $databasePort,
                'database' => $database,
                'username' => $databaseUsername,
                'password' => $databasePassword,
            ],
        ]);

        $tenant->save();
        event(new DatabaseCreated($tenant));

        $this->info(__('core::tenancy.commands.database.configuration_saved'));

        return Command::SUCCESS;
    }

    private function getUniqueDatabaseName(string $database, int $subfix = 0): string
    {
        $searchDb = $subfix > 0 ? $database . '_' . $subfix : $database;

        return $this->databaseExists($searchDb) ? $this->getUniqueDatabaseName($database, $subfix + 1) : $searchDb;
    }

    private function databaseExists(string $database): bool
    {
        return DB::selectOne(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$database],
        ) !== null;
    }

    private function databaseUserExists(string $username): bool
    {
        return DB::selectOne(
            'SELECT User FROM mysql.user WHERE User = ?',
            [$username],
        ) !== null;
    }

    private function createDatabaseUser(string $username, ?string $password): void
    {
        $safeUser = str_replace("'", '', $username);

        if ($password !== null && $password !== '') {
            DB::statement("CREATE USER '{$safeUser}'@'%' IDENTIFIED BY ?", [$password]);
        } else {
            DB::statement("CREATE USER '{$safeUser}'@'%'");
        }
    }

    private function grantPermissions(string $database, string $username): void
    {
        $safeDb = str_replace('`', '', $database);
        $safeUser = str_replace("'", '', $username);

        DB::statement("GRANT ALL ON `{$safeDb}`.* TO '{$safeUser}'@'%'");
    }

    private function flushPrivileges(): void
    {
        DB::statement('FLUSH PRIVILEGES');
    }
}
