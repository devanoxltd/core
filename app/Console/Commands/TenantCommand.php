<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

use function Devanox\Core\Helpers\tenancy;

#[Description('Execute an artisan command for a specific tenant')]
#[Signature('tenant {artisanCommand : The command to execute} {--tenant= : The tenant to execute the command for}')]
final class TenantCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenant = $this->option('tenant');

        /** @var class-string<TenantContract&Model> $tenantClass */
        $tenantClass = config('tenancy.models.tenant', \Devanox\Core\Models\Tenant::class);

        /** @var string $tenantKeyName */
        $tenantKeyName = (new $tenantClass)->getKeyName();

        /** @var Builder<TenantContract&Model> $query */
        $query = $tenantClass::query();

        $tenants = $query
            ->with([
                'domains',
            ])
            ->when($tenant, fn (Builder $query) => $query->where($tenantKeyName, $tenant));

        if ($tenants->count() === 0) {
            $this->error('No tenants found.');

            return Command::FAILURE;
        }

        $exitCode = Command::SUCCESS;

        foreach ($tenants->cursor() as $tenant) {
            /** @var TenantContract&Model $tenant */
            tenancy()->run($tenant, function () use (&$exitCode): void {
                $code = $this->runArtisanCommand();

                if ($code !== Command::SUCCESS) {
                    $exitCode = $code;
                }
            });
        }

        return $exitCode;
    }

    private function runArtisanCommand(): int
    {
        $tenant = tenancy()->tenant;

        if (! $tenant) {
            $this->error('No tenant is set.');

            return Command::FAILURE;
        }

        $tenantKey = $tenant->getKey();

        $this->info(sprintf(
            'Running command for tenant `%s` (id: %s)...',
            (string) $tenant->name,
            is_scalar($tenantKey) ? (string) $tenantKey : '',
        ));

        $artisanCommand = $this->argument('artisanCommand');

        if (! is_string($artisanCommand)) {
            $this->error('Invalid artisan command argument.');

            return Command::FAILURE;
        }

        $artisanCommand = addslashes($artisanCommand);

        $this->info("Executing command: `php artisan {$artisanCommand}`");
        $this->info('----------------------------------------');

        $result = Artisan::call($artisanCommand, outputBuffer: $this->output);

        $this->info('----------------------------------------');
        $this->newLine();

        return $result;
    }
}
