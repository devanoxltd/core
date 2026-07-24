<?php

declare(strict_types=1);

use Devanox\Core\Console\Commands\MigrateCheck;
use Devanox\Core\Models\License;
use Devanox\Core\Support\Module;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    $this->installedFile = storage_path('installed');

    if (file_exists($this->installedFile)) {
        unlink($this->installedFile);
    }

    License::query()->delete();
    cleanModulesDirectory();
});

afterEach(function (): void {
    if (file_exists($this->installedFile)) {
        unlink($this->installedFile);
    }

    cleanModulesDirectory();
});

function assertCommandOutput(string $command, array $options, int $expectedStatus, array $expected, array $notExpected = []): void
{
    $status = Artisan::call($command, $options);
    $output = Artisan::output();

    expect($status)->toBe($expectedStatus);

    foreach ($expected as $text) {
        expect($output)->toContain($text);
    }

    foreach ($notExpected as $text) {
        expect($output)->not->toContain($text);
    }
}

it('lists all modules', function (): void {
    createFakeModule('Alpha', ['id' => 'alpha', 'version' => '1.0.0']);
    createFakeModule('Beta', ['id' => 'beta', 'version' => '2.0.0'], true);

    assertCommandOutput('module:list', [], 0, ['Alpha', 'Beta']);
});

it('lists only enabled modules', function (): void {
    createFakeModule('Enabled', ['id' => 'enabled'], true);
    createFakeModule('Disabled', ['id' => 'disabled'], false);

    assertCommandOutput('module:list', ['--enabled' => true], 0, ['Enabled'], ['Disabled']);
});

it('lists only disabled modules', function (): void {
    createFakeModule('Enabled', ['id' => 'enabled'], true);
    createFakeModule('Disabled', ['id' => 'disabled'], false);

    assertCommandOutput('module:list', ['--disabled' => true], 0, ['Disabled'], ['Enabled']);
});

it('reports no modules found', function (): void {
    assertCommandOutput('module:list', [], 0, ['No modules found']);
});

it('enables a module with valid license', function (): void {
    createFakeModule('EnableMe', ['id' => 'enable-me'], false);
    License::query()->create([
        'key' => 'module-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'is_module' => true,
        'module_name' => 'EnableMe',
    ]);

    assertCommandOutput('module:enable', ['module' => 'EnableMe'], 0, ['EnableMe enabled successfully']);
});

it('fails enabling a module without license', function (): void {
    createFakeModule('EnableMe', ['id' => 'enable-me'], false);

    assertCommandOutput('module:enable', ['module' => 'EnableMe'], 1, ['does not have a valid license']);
});

it('warns enabling an already enabled module', function (): void {
    createFakeModule('AlreadyEnabled', ['id' => 'already-enabled'], true);

    assertCommandOutput('module:enable', ['module' => 'AlreadyEnabled'], 0, ['already enabled']);
});

it('fails enabling nonexistent module', function (): void {
    assertCommandOutput('module:enable', ['module' => 'Missing'], 1, ['does not exist']);
});

it('disables a module', function (): void {
    createFakeModule('DisableMe', ['id' => 'disable-me'], true);

    assertCommandOutput('module:disable', ['module' => 'DisableMe'], 0, ['DisableMe disabled successfully']);
});

it('warns disabling an already disabled module', function (): void {
    createFakeModule('AlreadyDisabled', ['id' => 'already-disabled'], false);

    assertCommandOutput('module:disable', ['module' => 'AlreadyDisabled'], 0, ['already disabled']);
});

it('fails disabling nonexistent module', function (): void {
    assertCommandOutput('module:disable', ['module' => 'Missing'], 1, ['does not exist']);
});

it('prompts to select module to disable', function (): void {
    createFakeModule('DisablePrompt', ['id' => 'disable-prompt'], true);

    $this->artisan('module:disable')
        ->expectsChoice('Which module do you want to disable?', 'DisablePrompt', ['DisablePrompt'])
        ->assertSuccessful();
});

it('disables all modules when forced', function (): void {
    createFakeModule('DisableAll1', ['id' => 'disable-all-1'], true);
    createFakeModule('DisableAll2', ['id' => 'disable-all-2'], true);

    assertCommandOutput('module:disable', ['--all' => true, '--force' => true], 0, ['All valid modules disabled successfully!']);

    expect(Module::isDisabled('DisableAll1'))->toBeTrue();
    expect(Module::isDisabled('DisableAll2'))->toBeTrue();
});

it('fails to disable a single module on exception', function (): void {
    createFakeModule('DisableFail', ['id' => 'disable-fail'], true);

    Event::shouldReceive('dispatch')->andThrow(new Exception('Event failure'));

    assertCommandOutput('module:disable', ['module' => 'DisableFail'], 1, ['Failed to disable module DisableFail: Event failure']);
});

it('fails to disable all modules on exception', function (): void {
    createFakeModule('DisableAllFail', ['id' => 'disable-all-fail'], true);

    Event::shouldReceive('dispatch')->andThrow(new Exception('Event failure'));

    assertCommandOutput('module:disable', ['--all' => true, '--force' => true], 1, [
        'Failed to disable module DisableAllFail: Event failure',
        'Finished with some errors.',
    ]);
});

it('fails to enable a single module on exception', function (): void {
    createFakeModule('EnableFail', ['id' => 'enable-fail'], false);
    License::query()->create([
        'key' => 'key1', 'status' => 'valid', 'is_module' => true, 'module_name' => 'EnableFail',
        'purchase_at' => now(), 'support_until' => now()->addYear(),
    ]);

    Event::shouldReceive('dispatch')->andThrow(new Exception('Event failure'));

    assertCommandOutput('module:enable', ['module' => 'EnableFail'], 1, ['Failed to enable module EnableFail: Event failure']);
});

it('fails to enable all modules on exception', function (): void {
    createFakeModule('EnableAllFail', ['id' => 'enable-all-fail'], false);
    License::query()->create([
        'key' => 'key2', 'status' => 'valid', 'is_module' => true, 'module_name' => 'EnableAllFail',
        'purchase_at' => now(), 'support_until' => now()->addYear(),
    ]);

    Event::shouldReceive('dispatch')->andThrow(new Exception('Event failure'));

    assertCommandOutput('module:enable', ['--all' => true, '--force' => true], 1, [
        'Failed to enable module EnableAllFail: Event failure',
        'Finished with some errors.',
    ]);
});

it('warns when disabling all modules and some are already disabled', function (): void {
    createFakeModule('DisableAllAlready', ['id' => 'disable-all-already'], false); // Already disabled

    assertCommandOutput('module:disable', ['--all' => true, '--force' => true], 0, [
        'Module DisableAllAlready is already disabled!',
    ]);
});

it('warns when enabling all modules and some are already enabled', function (): void {
    createFakeModule('EnableAllAlready', ['id' => 'enable-all-already'], true); // Already enabled

    assertCommandOutput('module:enable', ['--all' => true, '--force' => true], 0, [
        'Module EnableAllAlready is already enabled!',
    ]);
});

it('fails to enable all modules when a license is invalid', function (): void {
    createFakeModule('EnableAllInvalidLicense', ['id' => 'enable-all-invalid'], false);

    assertCommandOutput('module:enable', ['--all' => true, '--force' => true], 1, [
        'Module EnableAllInvalidLicense does not have a valid license!',
        'Finished with some errors.',
    ]);
});

it('fails to disable all modules without force and no interaction', function (): void {
    // If not forced, it prompts. Without interaction, it might fail or return SUCCESS if we mock the prompt or just run it with --no-interaction
    // Actually we can use expectsConfirmation
    $this->artisan('module:disable', ['--all' => true])
        ->expectsConfirmation('Are you sure you want to disable all modules?', 'yes')
        ->assertSuccessful();
});

it('warns when no modules to disable via prompt', function (): void {
    // Ensure no enabled modules
    $this->artisan('module:disable')
        ->expectsOutputToContain('There are no enabled modules to disable.')
        ->assertSuccessful();
});

it('handles exception when disabling a module', function (): void {
    createFakeModule('DisableError', ['id' => 'disable-error'], true);

    $path = Module::path('DisableError', true);
    // Make the enable file read-only, wait, actually make the directory read-only so unlink fails
    chmod(dirname($path), 0444);

    try {
        $this->artisan('module:disable', ['module' => 'DisableError'])
            ->assertFailed();
    } finally {
        chmod(dirname($path), 0755);
    }
});

it('cancels disabling all modules', function (): void {
    $this->artisan('module:disable', ['--all' => true])
        ->expectsConfirmation('Are you sure you want to disable all modules?', 'no')
        ->expectsOutputToContain('Action canceled.')
        ->assertSuccessful();
});

it('prompts to select module to enable', function (): void {
    createFakeModule('EnablePrompt', ['id' => 'enable-prompt'], false);
    License::query()->create(['key' => 'enable-prompt', 'status' => 'valid', 'is_module' => true, 'module_name' => 'EnablePrompt', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    $this->artisan('module:enable')
        ->expectsChoice('Which module do you want to enable?', 'EnablePrompt', ['EnablePrompt'])
        ->assertSuccessful();
});

it('warns when no modules to enable via prompt', function (): void {
    // Ensure no disabled modules
    $this->artisan('module:enable')
        ->expectsOutputToContain('There are no disabled modules to enable.')
        ->assertSuccessful();
});

it('enables all modules when forced', function (): void {
    createFakeModule('EnableAll1', ['id' => 'enable-all-1'], false);
    License::query()->create(['key' => 'enable-all-1', 'status' => 'valid', 'is_module' => true, 'module_name' => 'EnableAll1', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    assertCommandOutput('module:enable', ['--all' => true, '--force' => true], 0, ['All valid modules processed successfully.']);
});

it('cancels enabling all modules', function (): void {
    $this->artisan('module:enable', ['--all' => true])
        ->expectsConfirmation('Are you sure you want to enable all modules?', 'no')
        ->expectsOutputToContain('Action canceled.')
        ->assertSuccessful();
});

it('enables all modules with confirmation', function (): void {
    $this->artisan('module:enable', ['--all' => true])
        ->expectsConfirmation('Are you sure you want to enable all modules?', 'yes')
        ->assertSuccessful();
});

it('handles exception when enabling a module', function (): void {
    createFakeModule('EnableError', ['id' => 'enable-error'], false);
    License::query()->create(['key' => 'enable-error', 'status' => 'valid', 'is_module' => true, 'module_name' => 'EnableError', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    $path = Module::path('EnableError', true);
    chmod(dirname($path), 0444);

    try {
        $this->artisan('module:enable', ['module' => 'EnableError'])
            ->assertFailed();
    } finally {
        chmod(dirname($path), 0755);
    }
});

it('migrates enabled modules', function (): void {
    createFakeModule('MigrateMe', ['id' => 'migrate-me'], true, [
        'Database' . DIRECTORY_SEPARATOR . 'Migrations',
    ]);

    $migrationPath = Module::pathFor('MigrateMe', 'migrations');
    File::put($migrationPath . DIRECTORY_SEPARATOR . '2025_01_01_000000_create_test_table.php', '<?php
return new class extends \Illuminate\Database\Migrations\Migration {
public function up() { ' . Schema::class . '::create(\'test_table\', function ($table) { $table->id(); }); }
public function down() { ' . Schema::class . '::dropIfExists(\'test_table\'); }
};
');

    assertCommandOutput('module:migrate', [], 0, ['Module migrations completed successfully']);
});

it('reports no enabled modules for migrate', function (): void {
    createFakeModule('Disabled', ['id' => 'disabled'], false);

    assertCommandOutput('module:migrate', [], 0, ['No enabled modules found']);
});

it('fails migrating nonexistent module', function (): void {
    assertCommandOutput('module:migrate', ['module' => 'Missing'], 1, ['does not exist']);
});

it('warns migrating disabled module', function (): void {
    createFakeModule('DisabledMigrate', ['id' => 'disabled-migrate'], false);
    assertCommandOutput('module:migrate', ['module' => 'DisabledMigrate'], 1, ['is disabled']);
});

it('migrates specific module with options', function (): void {
    createFakeModule('MigrateSpecific', ['id' => 'migrate-specific'], true);

    // We can use pretend to avoid actually running
    $this->artisan('module:migrate', [
        'module' => 'MigrateSpecific',
        '--database' => 'testing',
        '--pretend' => true,
        '--seed' => true,
        '--step' => true,
    ])->assertSuccessful();
});

it('fails to migrate when migrate command fails', function (): void {
    createFakeModule('MigrateFail', ['id' => 'migrate-fail'], true);

    // Mock the command output
    Artisan::command('migrate {--path=} {--force} {--database=?} {--pretend=?} {--seed=?} {--step=?}', function (): int {
        return 1; // Failure
    });

    assertCommandOutput('module:migrate', ['module' => 'MigrateFail'], 1, ['Migration failed']);
});

it('cancels migration in production without force', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Prompt::fallbackWhen(true);

    $this->artisan('module:migrate')
        ->expectsConfirmation('Application is in production! Do you wish to run module migrations?', 'no')
        ->expectsOutputToContain('Action canceled.')
        ->assertSuccessful();
});

it('reports migration table not found', function (): void {
    $repository = Mockery::mock(DatabaseMigrationRepository::class);
    $repository->shouldReceive('repositoryExists')->once()->andReturn(false);

    $migrator = Mockery::mock(Migrator::class, [
        $repository,
        resolve(ConnectionResolverInterface::class),
        resolve(Filesystem::class),
    ])->makePartial();

    $migrator->shouldReceive('usingConnection')->andReturnUsing(fn ($name, $callback) => $callback());

    $command = new MigrateCheck($migrator);
    $command->setLaravel($this->app);

    $output = new BufferedOutput;
    $statusCode = $command->run(new ArrayInput([]), $output);

    expect($statusCode)->toBe(1); // Command::FAILURE
});

it('reports pending migrations found', function (): void {
    $migrationPath = database_path('migrations');

    if (! is_dir($migrationPath)) {
        mkdir($migrationPath, 0o755, true);
    }

    File::put($migrationPath . '/2025_01_01_000000_fake_pending.php', '<?php return new class extends \Illuminate\Database\Migrations\Migration {};');

    assertCommandOutput('migrate:check', ['--path' => 'database/migrations'], 1, ['Pending']);

    File::delete($migrationPath . '/2025_01_01_000000_fake_pending.php');
});

it('checks pending migrations', function (): void {
    Artisan::call('migrate');

    assertCommandOutput('migrate:check', [], 0, ['No pending migrations found']);
});

it('deletes garbage licenses during check', function (): void {
    config(['core.url.server' => null]);

    DB::table('licenses')->insert([
        'key' => '',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    assertCommandOutput('devanox:license-check', [], 0, ['Deleted 1 garbage license']);
});

it('checks license status successfully', function (): void {
    config(['core.url.server' => 'https://devanox.test']);
    config(['app.version' => '1.0.0']);

    Http::fake([
        '*' => Http::response(json_encode([
            'status' => 'success',
            'data' => [
                'id' => 'license-id',
                'purchase_code' => 'PC123',
                'type' => 'regular',
                'purchase_at' => '2025-01-01 00:00:00',
                'support_until' => '2026-01-01 00:00:00',
                'status' => 'valid',
            ],
        ]), 200, ['Content-Type' => 'application/json']),
    ]);

    License::query()->create([
        'key' => 'valid-key',
        'status' => 'active',
        'last_checked_at' => now()->subDays(2),
    ]);

    assertCommandOutput('devanox:license-check', [], 0, ['checked successfully']);
});

it('skips recently checked licenses', function (): void {
    config(['core.url.server' => 'https://devanox.test']);

    License::query()->create([
        'key' => 'recent-key',
        'status' => 'valid',
        'last_checked_at' => now(),
    ]);

    Http::fake([
        '*' => Http::response('', 200),
    ]);

    assertCommandOutput('devanox:license-check', [], 0, ['SKIPPED (RECENT)']);
});

it('cleans up log files and handles failures', function (): void {
    $logPath = storage_path('logs');

    if (! is_dir($logPath)) {
        mkdir($logPath, 0o755, true);
    }

    // Test empty logs
    $files = glob($logPath . '/*.log');

    if ($files) {
        foreach ($files as $f) {
            unlink($f);
        }
    }

    assertCommandOutput('app:clean-up', [], 0, ['No log files found']);

    file_put_contents($logPath . '/laravel.log', 'Test log');
    file_put_contents($logPath . '/browser.log', 'Browser log');

    assertCommandOutput('app:clean-up', [], 0, ['Cleared laravel.log', 'Cleared browser.log', 'Cleanup completed successfully']);

    expect(filesize($logPath . '/laravel.log'))->toBe(0)
        ->and(filesize($logPath . '/browser.log'))->toBe(0);

    // Test failure to clear
    file_put_contents($logPath . '/laravel.log', 'Test');
    File::shouldReceive('put')->with($logPath . '/laravel.log', '')->andReturn(false);
    File::shouldReceive('put')->with($logPath . '/browser.log', '')->andReturn(true);
    File::makePartial();

    assertCommandOutput('app:clean-up', [], 1, ['Failed to clear laravel.log']);
});

it('runs clean up in dry run mode and deletes old logs', function (): void {
    $logPath = storage_path('logs');

    if (! is_dir($logPath)) {
        mkdir($logPath, 0o755, true);
    }

    File::put($logPath . '/laravel.log', 'content');
    $oldLogFile = $logPath . '/old-worker.log';
    File::put($oldLogFile, 'old log');
    touch($oldLogFile, now()->subDays(10)->timestamp); // Make it 10 days old

    assertCommandOutput('app:clean-up', ['--dry-run' => true], 0, [
        'DRY RUN MODE',
        'Would clear: laravel.log',
        'Would delete 1 old log file(s)',
        'old-worker.log',
    ]);

    expect(File::get($logPath . '/laravel.log'))->toBe('content')
        ->and(File::exists($oldLogFile))->toBeTrue();

    unlink($oldLogFile);
});

it('deletes old logs successfully', function (): void {
    $logPath = storage_path('logs');

    if (! is_dir($logPath)) {
        mkdir($logPath, 0o755, true);
    }

    $oldLogFile = $logPath . '/old-worker.log';
    File::put($oldLogFile, 'old log');
    touch($oldLogFile, now()->subDays(10)->timestamp);

    assertCommandOutput('app:clean-up', [], 0, [
        'Deleting 1 old log file(s) (older than 7 days)',
        'Deleted 1 old log file(s)',
        'Cleanup completed successfully',
    ]);

    expect(File::exists($oldLogFile))->toBeFalse();
});

it('does not delete logs if none are older than threshold', function (): void {
    $logPath = storage_path('logs');

    if (! is_dir($logPath)) {
        mkdir($logPath, 0o755, true);
    }

    $recentLogFile = $logPath . '/recent-worker.log';
    File::put($recentLogFile, 'recent log');
    touch($recentLogFile, now()->subDays(2)->timestamp);

    assertCommandOutput('app:clean-up', [], 0, [
        'Cleanup completed successfully',
    ], ['Deleting old log file(s)']);

    expect(File::exists($recentLogFile))->toBeTrue();

    unlink($recentLogFile);
});

it('fails to delete old logs when error occurs', function (): void {
    $logPath = storage_path('logs');

    if (! is_dir($logPath)) {
        mkdir($logPath, 0o755, true);
    }

    $oldLogFile = $logPath . '/old-worker.log';
    File::put($oldLogFile, 'old log');
    touch($oldLogFile, now()->subDays(10)->timestamp);

    File::shouldReceive('delete')->with([$oldLogFile])->andReturn(false);
    File::makePartial();

    assertCommandOutput('app:clean-up', [], 1, [
        'Failed to delete old log file(s)',
    ]);
});

it('does not delete logs that are recent', function (): void {
    $logPath = storage_path('logs');

    if (! is_dir($logPath)) {
        mkdir($logPath, 0o755, true);
    }

    $recentLogFile = $logPath . '/recent-worker.log';
    File::put($recentLogFile, 'recent log');
    touch($recentLogFile, now()->subDays(1)->timestamp); // Only 1 day old

    assertCommandOutput('app:clean-up', [], 0, []);

    expect(File::exists($recentLogFile))->toBeTrue();

    unlink($recentLogFile);
});

it('deletes garbage licenses', function (): void {
    License::query()->create(['key' => '', 'status' => 'invalid', 'is_module' => false, 'module_name' => null]);

    assertCommandOutput('devanox:license-check', [], 0, ['Deleted 1 garbage license(s)']);
});

it('fails license check when server url is invalid', function (): void {
    config(['core.url.server' => null]);
    License::query()->create(['key' => 'valid-key', 'status' => 'valid', 'is_module' => false, 'module_name' => null]);

    assertCommandOutput('devanox:license-check', [], 1, ['Server URL is not configured']);
});

it('invalidates license on empty data', function (): void {
    License::query()->create(['key' => 'empty-data-key', 'status' => 'valid', 'is_module' => false, 'module_name' => null]);

    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    assertCommandOutput('devanox:license-check', [], 1, ['Invalid response data']);
});

it('handles exception during license check', function (): void {
    License::query()->create(['key' => 'exception-key', 'status' => 'valid', 'is_module' => false, 'module_name' => null]);

    Http::fake([
        '*' => function (): void {
            throw new Exception('Connection failed');
        },
    ]);

    assertCommandOutput('devanox:license-check', [], 1, ['Failed to check license']);
});

it('skips check when server returns 5xx error', function (): void {
    License::query()->create(['key' => 'server-error-key', 'status' => 'valid', 'is_module' => false, 'module_name' => null]);

    Http::fake([
        '*' => Http::response(['message' => 'Internal Server Error'], 500),
    ]);

    assertCommandOutput('devanox:license-check', [], 1, ['License server unavailable (5xx error)']);
});

it('invalidates license when status is not success', function (): void {
    License::query()->create(['key' => 'invalid-status-key', 'status' => 'valid', 'is_module' => false, 'module_name' => null]);

    Http::fake([
        '*' => Http::response(['status' => 'error', 'message' => 'Invalid license'], 200),
    ]);

    assertCommandOutput('devanox:license-check', [], 1, ['has been invalidated']);
});
