<?php

declare(strict_types=1);

use Devanox\Core\Console\Commands\TenantQueueManageCommand;
use Devanox\Core\Models\Tenant;
use Illuminate\Console\Signals;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;

function managerTestTenant(string $name): Tenant
{
    return Tenant::withoutEvents(fn (): Tenant => Tenant::query()->forceCreate([
        'user_id' => 1,
        'name' => $name,
        'email' => 'tenant@example.com',
        'status' => 'active',
        'config' => ['database' => ['driver' => 'sqlite', 'database' => ':memory:']],
    ]));
}

#[Table(name: 'tenants')]
final class InvalidKeyManagerTenant extends Tenant
{
    public function getKey(): mixed
    {
        return '';
    }
}

it('discovers tenants and starts an isolated worker for each tenant', function (): void {
    $first = managerTestTenant('First');
    $second = managerTestTenant('Second');
    Process::fake();

    $exitCode = Artisan::call('tenant:queue-manage', [
        '--once' => true,
        '--workers' => '1',
        '--queue' => 'imports, default',
        '--env' => 'testing',
        '--force' => true,
    ]);

    expect($exitCode)->toBe(0);
    Process::assertRanTimes(fn (): bool => true, 2);

    foreach ([$first, $second] as $tenant) {
        Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command)
            && in_array('tenant:queue-work', $process->command, true)
            && in_array((string) $tenant->getKey(), $process->command, true)
            && in_array('--stop-when-empty', $process->command, true)
            && in_array('--max-jobs=1', $process->command, true)
            && in_array('--env=testing', $process->command, true)
            && in_array('--force', $process->command, true)
            && in_array('--queue=imports,default', $process->command, true));
    }
})->skipOnWindows();

it('discovers only active tenants as their status changes', function (): void {
    $active = managerTestTenant('Active');
    $suspended = managerTestTenant('Suspended');
    $suspended->updateQuietly(['status' => 'suspended']);
    Process::fake();

    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(0);
    $active->updateQuietly(['status' => 'suspended']);
    $suspended->updateQuietly(['status' => 'active']);
    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(0);

    Process::assertRanTimes(fn (): bool => true, 2);

    foreach ([$active, $suspended] as $tenant) {
        Process::assertRanTimes(fn (PendingProcess $process): bool => is_array($process->command)
            && in_array((string) $tenant->getKey(), $process->command, true), 1);
    }
})->skipOnWindows();

it('rotates queue priority between discovery passes', function (): void {
    managerTestTenant('Active');
    Process::fake();
    $options = ['--once' => true, '--queue' => 'imports,default'];

    expect(Artisan::call('tenant:queue-manage', $options))->toBe(0)
        ->and(Artisan::call('tenant:queue-manage', $options))->toBe(0);

    foreach (['imports,default', 'default,imports'] as $queue) {
        Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command)
            && in_array('--queue=' . $queue, $process->command, true));
    }
})->skipOnWindows();

it('shows worker output even when a failed job does not fail the process and skips blank output', function (): void {
    managerTestTenant('Active');
    managerTestTenant('Blank');
    Process::fake([
        '*' => Process::sequence()
            ->push(Process::result(output: 'ExampleJob ... FAIL'))
            ->push(Process::result(output: "   \n")),
    ]);

    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('ExampleJob ... FAIL');
})->skipOnWindows();

it('discovers new tenants on a later pass', function (): void {
    $first = managerTestTenant('First');
    Process::fake();
    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(0);
    $second = managerTestTenant('Second');

    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(0);

    Process::assertRanTimes(fn (): bool => true, 3);
    Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command)
        && in_array((string) $first->getKey(), $process->command, true));
    Process::assertRan(fn (PendingProcess $process): bool => is_array($process->command)
        && in_array((string) $second->getKey(), $process->command, true));
})->skipOnWindows();

it('waits for a worker slot before starting another tenant', function (): void {
    managerTestTenant('First');
    managerTestTenant('Second');
    Process::fake(fn (): mixed => Process::describe()->iterations(2));

    expect(Artisan::call('tenant:queue-manage', ['--once' => true, '--workers' => '1']))->toBe(0);

    Process::assertRanTimes(fn (): bool => true, 2);
    Sleep::assertSleptTimes(4);
})->skipOnWindows();

it('discovers tenants beyond the first page', function (): void {
    for ($i = 0; $i < 101; $i++) {
        managerTestTenant('Tenant ' . $i);
    }

    Process::fake();

    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(0);

    Process::assertRanTimes(fn (): bool => true, 101);
})->skipOnWindows();

it('reports worker failures and start exceptions without skipping other tenants', function (): void {
    managerTestTenant('First');
    managerTestTenant('Second');
    $attempt = 0;
    Process::fake(function () use (&$attempt): mixed {
        $attempt++;

        throw_if($attempt === 1, RuntimeException::class, 'Spawn failed');

        return Process::result(errorOutput: 'Database unavailable', exitCode: 1);
    });

    $exitCode = Artisan::call('tenant:queue-manage', ['--once' => true, '--workers' => '1']);
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('Unable to start the worker')
        ->and($output)->toContain('Database unavailable');
    Process::assertRanTimes(fn (): bool => true, 1);
})->skipOnWindows();

it('rejects invalid configuration and invalid tenant identifiers before starting workers', function (): void {
    managerTestTenant('First');
    Process::fake();

    expect(Artisan::call('tenant:queue-manage', ['--once' => true, '--workers' => '0']))->toBe(1)
        ->and(Artisan::call('tenant:queue-manage', ['--once' => true, '--tries' => '-1']))->toBe(1)
        ->and(Artisan::call('tenant:queue-manage', ['--once' => true, '--backoff' => 'bad']))->toBe(1);

    Config::set('tenancy.models.tenant', stdClass::class);
    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(1);

    Config::set('tenancy.models.tenant', InvalidKeyManagerTenant::class);
    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(1)
        ->and(Artisan::output())->toContain('invalid identifier');

    Config::set('tenancy.enabled', false);
    expect(Artisan::call('tenant:queue-manage', ['--once' => true]))->toBe(1);

    Process::assertNothingRan();
})->skipOnWindows();

it('stops gracefully between discovery passes on signal', function (): void {
    managerTestTenant('First');
    managerTestTenant('Second');

    Signals::resolveAvailabilityUsing(fn (): bool => true);

    try {
        Process::fake();
        $sleepCount = 0;
        Sleep::whenFakingSleep(function () use (&$sleepCount): void {
            $sleepCount++;

            if ($sleepCount === 1) {
                $command = Artisan::all()['tenant:queue-manage'];
                $command->getApplication()?->getSignalRegistry()->handle(SIGTERM);
            }
        });

        expect(Artisan::call('tenant:queue-manage', ['--interval' => '1']))->toBe(0);
        Process::assertRanTimes(fn (): bool => true, 2);
    } finally {
        Signals::resolveAvailabilityUsing(fn (): bool => ! app()->runningUnitTests() && extension_loaded('pcntl'));
        expect(Artisan::all()['tenant:queue-manage'])->toBeInstanceOf(TenantQueueManageCommand::class);
    }
})->skipOnWindows();

it('stops gracefully while waiting for a worker slot on signal', function (): void {
    managerTestTenant('First');
    managerTestTenant('Second');

    Signals::resolveAvailabilityUsing(fn (): bool => true);

    try {
        Process::fake(fn (): mixed => Process::describe()->iterations(2));
        $slotSleepCount = 0;
        Sleep::whenFakingSleep(function () use (&$slotSleepCount): void {
            $slotSleepCount++;

            if ($slotSleepCount === 1) {
                $command = Artisan::all()['tenant:queue-manage'];
                $command->getApplication()?->getSignalRegistry()->handle(SIGTERM);
            }
        });

        expect(Artisan::call('tenant:queue-manage', ['--workers' => '1', '--interval' => '1']))->toBe(0);
        Process::assertRanTimes(fn (): bool => true, 1);
    } finally {
        Signals::resolveAvailabilityUsing(fn (): bool => ! app()->runningUnitTests() && extension_loaded('pcntl'));
    }
})->skipOnWindows();

it('stops gracefully at the start of the tenant loop on signal', function (): void {
    managerTestTenant('First');
    managerTestTenant('Second');

    Signals::resolveAvailabilityUsing(fn (): bool => true);

    try {
        Process::fake(function (): mixed {
            $command = Artisan::all()['tenant:queue-manage'];
            $command->getApplication()?->getSignalRegistry()->handle(SIGTERM);

            return Process::result();
        });

        expect(Artisan::call('tenant:queue-manage', ['--workers' => '2', '--interval' => '1']))->toBe(0);
        Process::assertRanTimes(fn (): bool => true, 1);
    } finally {
        Signals::resolveAvailabilityUsing(fn (): bool => ! app()->runningUnitTests() && extension_loaded('pcntl'));
    }
})->skipOnWindows();
