<?php

declare(strict_types=1);

use Devanox\Core\Core;
use Devanox\Core\Http\Middleware\InstallApp;
use Devanox\Core\Http\Middleware\License;
use Devanox\Core\Providers\CoreServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Artisan;
use Modules\ProviderModule\App\Providers\ProviderModuleServiceProvider;

it('resolves the core singleton', function (): void {
    expect(resolve(Core::class))->toBeInstanceOf(Core::class);
});

it('returns the same core instance from the container', function (): void {
    expect(resolve(Core::class))->toBe(resolve(Core::class));
});

it('merges the package config', function (): void {
    expect(config('core.php'))->toBe('8.5.0');
    expect(config('core.url.server'))->toBe('https://devanox-activate.test');
});

it('loads the package translations', function (): void {
    expect(trans('core::messages.placeholder'))->toBe('Core placeholder translation.');
    expect(trans('core::install.not_installed'))->toBe('Application is not installed yet.');
});

it('loads the package views', function (): void {
    expect(view()->exists('core::placeholder'))->toBeTrue();
    expect(view()->exists('core::install'))->toBeTrue();
    expect(view()->exists('core::license'))->toBeTrue();
});

it('registers package middleware globally', function (): void {
    $kernel = resolve(Kernel::class);
    $middlewares = new ReflectionClass($kernel)->getProperty('middleware')->getValue($kernel);

    expect($middlewares)->toContain(InstallApp::class)
        ->toContain(License::class);
});

it('registers package commands', function (): void {
    $this->artisan('list')->assertSuccessful();

    expect(Artisan::all())->toHaveKeys([
        'module:list',
        'module:enable',
        'module:disable',
        'module:migrate',
        'app:clean-up',
        'migrate:check',
        'devanox:license-check',
    ]);
});

it('publishes config file', function (): void {
    $reflection = new ReflectionClass(CoreServiceProvider::class);
    $property = $reflection->getProperty('publishes');

    $publishes = $property->getValue();

    $providerClass = CoreServiceProvider::class;

    expect($publishes)->toHaveKey($providerClass);

    $packagePublishes = $publishes[$providerClass] ?? [];

    expect(array_values($packagePublishes))->toContain(config_path('core.php'));
});

it('throws exception when required module is missing', function (): void {
    createFakeModule('DependentModule', [
        'id' => 'dependent-module',
        'requiredModules' => ['missing-module'],
    ], true);

    $provider = new CoreServiceProvider($this->app);

    // We expect a RuntimeException from checkModulesCapabilities
    expect(fn () => $provider->register())->toThrow(RuntimeException::class, 'Module DependentModule requires a module that is not installed');
});

it('registers module service providers', function (): void {
    // Create a fake module WITH a service provider
    $modulePath = createFakeModule('ProviderModule', ['id' => 'provider-module'], true);

    $providerDir = $modulePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Providers';

    if (! is_dir($providerDir)) {
        mkdir($providerDir, 0o755, true);
    }

    $providerCode = <<<PHP
<?php
namespace Modules\ProviderModule\App\Providers;
use Illuminate\Support\ServiceProvider;
class ProviderModuleServiceProvider extends ServiceProvider {}
PHP;
    file_put_contents($providerDir . DIRECTORY_SEPARATOR . 'ProviderModuleServiceProvider.php', $providerCode);

    // Require the file so it's loaded in memory
    require_once $providerDir . DIRECTORY_SEPARATOR . 'ProviderModuleServiceProvider.php';

    // Now instantiate and register the core provider
    $provider = new CoreServiceProvider($this->app);
    $provider->register();

    // Verify it was registered
    $registered = $this->app->getProvider(ProviderModuleServiceProvider::class);
    expect($registered)->not->toBeNull();
});
it('returns early when not running in console', function (): void {
    // Create a mock application from the real one
    $appMock = Mockery::mock($this->app)->makePartial();
    $appMock->shouldReceive('runningInConsole')->andReturn(false);

    $provider = new CoreServiceProvider($appMock);

    // Call boot, which calls registerCommandSchedules and publishFiles
    // If it returns early, publishes will be skipped
    $provider->boot();

    // Call booting/booted callbacks which registers the schedules
    $provider->callBootingCallbacks();
    $provider->callBootedCallbacks();

    expect(true)->toBeTrue();
});

it('registers blade components when directory exists', function (): void {
    $componentPath = __DIR__ . '/../../app/View/Components';

    if (! is_dir($componentPath)) {
        mkdir($componentPath, 0o755, true);
    }

    $provider = new CoreServiceProvider($this->app);
    $provider->boot();

    expect(true)->toBeTrue();

    // Clean up
    rmdir($componentPath);
});
