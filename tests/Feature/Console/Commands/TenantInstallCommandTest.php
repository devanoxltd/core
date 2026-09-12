<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $pid = getmypid();
    $configPath = __DIR__ . '/temp/config-' . $pid;
    $appPath = __DIR__ . '/temp/app-' . $pid;
    $databasePath = __DIR__ . '/temp/database-' . $pid;

    File::ensureDirectoryExists($configPath);
    File::ensureDirectoryExists($appPath . '/Models');
    File::ensureDirectoryExists($databasePath . '/migrations');

    app()->useConfigPath($configPath);
    app()->useAppPath($appPath);
    app()->useDatabasePath($databasePath);
});

afterEach(function (): void {
    $pid = getmypid();
    File::deleteDirectory(__DIR__ . '/temp/config-' . $pid);
    File::deleteDirectory(__DIR__ . '/temp/app-' . $pid);
    File::deleteDirectory(__DIR__ . '/temp/database-' . $pid);
});

it('installs tenancy and creates the tenant model', function (): void {
    $this->artisan('tenancy:install')
        ->expectsOutput('Installing Tenancy Package...')
        ->expectsOutput('Created: app/Models/Tenant.php')
        ->expectsOutput('Tenancy installed successfully! Update config/tenancy.php to use your new App\Models\Tenant.')
        ->assertSuccessful();

    expect(File::exists(app_path('Models/Tenant.php')))->toBeTrue();
});

it('skips creating the tenant model if it already exists', function (): void {
    File::ensureDirectoryExists(app_path('Models'));
    File::put(app_path('Models/Tenant.php'), '<?php // fake');

    $this->artisan('tenancy:install')
        ->expectsOutput('Installing Tenancy Package...')
        ->expectsOutput('Model app/Models/Tenant.php already exists. Skipping.')
        ->expectsOutput('Tenancy installed successfully! Update config/tenancy.php to use your new App\Models\Tenant.')
        ->assertSuccessful();
});
