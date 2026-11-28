<?php

declare(strict_types=1);

use Devanox\Core\Console\Commands\TenantCommand;
use Devanox\Core\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

use function Devanox\Core\Helpers\tenancy;

beforeEach(function (): void {
    // Create some fake tenants for testing
    Tenant::query()->forceDelete();

    $this->tenant1 = Tenant::withoutEvents(fn () => Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => 'active',
        'config' => ['database' => ['database' => 'tenant_1', 'driver' => 'mysql']],
    ]));

    $this->tenant2 = Tenant::withoutEvents(fn () => Tenant::query()->forceCreate([
        'id' => 2,
        'user_id' => 1,
        'name' => 'Tenant 2',
        'email' => 'test@example.com',
        'status' => 'active',
        'config' => ['database' => ['database' => 'tenant_2', 'driver' => 'mysql']],
    ]));
});

it('executes command for all tenants when no specific tenant is provided', function (): void {
    $outputBuffer = new BufferedOutput;
    $status = Artisan::call('tenant', [
        'artisanCommand' => 'env',
    ], $outputBuffer);

    $output = $outputBuffer->fetch();

    expect($status)->toBe(0)
        ->and($output)->toContain('Running command for tenant `Tenant 1` (id: 1)')
        ->and($output)->toContain('Running command for tenant `Tenant 2` (id: 2)');
});

it('executes command for specific tenant', function (): void {
    $outputBuffer = new BufferedOutput;
    $status = Artisan::call('tenant', [
        'artisanCommand' => 'env',
        '--tenant' => 2,
    ], $outputBuffer);

    $output = $outputBuffer->fetch();

    expect($status)->toBe(0)
        ->and($output)->not->toContain('Running command for tenant `Tenant 1` (id: 1)')
        ->and($output)->toContain('Running command for tenant `Tenant 2` (id: 2)');
});

it('fails when no tenants are found', function (): void {
    $outputBuffer = new BufferedOutput;
    $status = Artisan::call('tenant', [
        'artisanCommand' => 'env',
        '--tenant' => 999,
    ], $outputBuffer);

    $output = $outputBuffer->fetch();

    expect($status)->toBe(1)
        ->and($output)->toContain('No tenants found.');
});

it('does not execute tenant commands when tenancy is disabled', function (): void {
    config(['tenancy.enabled' => false]);
    $executed = false;
    Artisan::command('tenant-disabled-test', function () use (&$executed): void {
        $executed = true;
    });

    expect(Artisan::call('tenant', ['artisanCommand' => 'tenant-disabled-test']))->toBe(1)
        ->and($executed)->toBeFalse();
});

it('fails when tenant is not set in tenancy', function (): void {
    // This is hard to reach normally, but we can call runArtisanCommand via reflection
    $command = $this->app->make(TenantCommand::class);
    $reflection = new ReflectionMethod($command, 'runArtisanCommand');

    $output = Mockery::mock(OutputStyle::class)->makePartial();
    $output->shouldReceive('writeln')->andReturn();
    $command->setOutput($output);

    // Tenancy has no tenant by default in tests without setup
    $result = $reflection->invoke($command);
    expect($result)->toBe(Command::FAILURE);
});

it('fails when artisan command argument is invalid', function (): void {
    $command = $this->app->make(TenantCommand::class);
    $reflection = new ReflectionMethod($command, 'runArtisanCommand');

    $tenant = Tenant::withoutEvents(fn () => Tenant::query()->forceCreate([
        'id' => 99,
        'user_id' => 1,
        'name' => 'Tenant 99',
        'email' => 'test99@example.com',
        'status' => 'active',
        'config' => ['database' => ['database' => ':memory:', 'driver' => 'sqlite']],
    ]));

    tenancy()->setTenant($tenant);

    $input = Mockery::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('artisanCommand')->andReturn(['array']);
    $command->setInput($input);

    $output = Mockery::mock(OutputStyle::class)->makePartial();
    $output->shouldReceive('writeln')->andReturn();
    $command->setOutput($output);

    $result = $reflection->invoke($command);
    expect($result)->toBe(Command::FAILURE);

    tenancy()->unsetTenant();
});

it('fails when artisan command fails', function (): void {
    Artisan::command('fail-command', fn (): int => 1);

    $outputBuffer = new BufferedOutput;
    $status = Artisan::call('tenant', [
        'artisanCommand' => 'fail-command',
    ], $outputBuffer);

    expect($status)->toBe(1);
});

it('reports a failure when a tenant database context cannot be initialized', function (): void {
    $invalidTenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'id' => 3,
        'user_id' => 1,
        'name' => 'Tenant 3',
        'email' => 'test3@example.com',
        'status' => 'active',
        'config' => [],
    ]));

    $outputBuffer = new BufferedOutput;
    $status = Artisan::call('tenant', [
        'artisanCommand' => 'env',
        '--tenant' => $invalidTenant->id,
    ], $outputBuffer);

    expect($status)->toBe(1)
        ->and($outputBuffer->fetch())->toContain('Tenant database context could not be initialized.');
});

it('fails when no tenant is set internally', function (): void {
    $command = new TenantCommand;
    $command->setLaravel($this->app);

    $output = new BufferedOutput;
    $command->setOutput(new OutputStyle(
        new ArrayInput([]), $output,
    ));

    $reflection = new ReflectionMethod($command, 'runArtisanCommand');

    tenancy()->unsetTenant();

    $status = $reflection->invoke($command);

    expect($status)->toBe(1)
        ->and($output->fetch())->toContain('No tenant is set.');
});
