<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    if (File::exists(app_path('Models/Tenant.php'))) {
        File::delete(app_path('Models/Tenant.php'));
    }

    if (File::exists(config_path('tenancy.php'))) {
        File::delete(config_path('tenancy.php'));
    }

    File::cleanDirectory(database_path('migrations'));
});

afterEach(function (): void {
    if (File::exists(app_path('Models/Tenant.php'))) {
        File::delete(app_path('Models/Tenant.php'));
    }

    if (File::exists(config_path('tenancy.php'))) {
        File::delete(config_path('tenancy.php'));
    }

    File::cleanDirectory(database_path('migrations'));
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
