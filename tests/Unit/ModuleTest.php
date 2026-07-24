<?php

declare(strict_types=1);

use Devanox\Core\Events\ModuleDisabled;
use Devanox\Core\Events\ModuleEnabled;
use Devanox\Core\Support\Module;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    cleanModulesDirectory();
});

afterEach(function (): void {
    cleanModulesDirectory();
});

it('can get module path', function (): void {
    $path = Module::path('TestModule');
    $expectedPath = config('core.module_path', Module::MODULES_PATH) . DIRECTORY_SEPARATOR . 'TestModule';
    expect($path)->toBe($expectedPath);

    $fullPath = Module::path('TestModule', true);
    expect($fullPath)->toBe(base_path($expectedPath));

    $enablePath = Module::path('TestModule', false, true);
    expect($enablePath)->toBe($expectedPath . DIRECTORY_SEPARATOR . 'enable');
});

it('lists all modules', function (): void {
    createFakeModule('ModuleA');
    createFakeModule('ModuleB');

    $all = Module::all();
    expect($all)->toContain('ModuleA', 'ModuleB')->toHaveCount(2);
});

it('gets module config with caching', function (): void {
    createFakeModule('ConfigModule', ['id' => 'config-module', 'version' => '1.0']);

    $config = Module::config('ConfigModule');
    expect($config)->toBeArray()
        ->toHaveKey('id', 'config-module')
        ->toHaveKey('version', '1.0');

    // It should hit cache on second call
    $config2 = Module::config('ConfigModule');
    expect($config2)->toBe($config);
});

it('returns empty array when module config does not exist', function (): void {
    expect(Module::config('NonExistentModule'))->toBe([]);

    $modulePath = createFakeModule('NoConfigModule');
    // Remove the config file specifically to test config missing
    $configFile = $modulePath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';

    if (file_exists($configFile)) {
        unlink($configFile);
    }

    expect(Module::config('NoConfigModule'))->toBe([]);
});

it('validates module by checking config id', function (): void {
    createFakeModule('ValidModule', ['id' => 'valid-module']);
    createFakeModule('InvalidModule', []);

    expect(Module::isValid('ValidModule'))->toBeTrue();
    expect(Module::isValid('InvalidModule'))->toBeFalse();
});

it('checks if module exists', function (): void {
    createFakeModule('ExistingModule');

    expect(Module::exist('ExistingModule'))->toBeTrue();
    expect(Module::exist('NonExistingModule'))->toBeFalse();
});

it('checks module enabled/disabled state', function (): void {
    createFakeModule('EnabledModule', [], true);
    createFakeModule('DisabledModule', [], false);

    expect(Module::isEnabled('EnabledModule'))->toBeTrue();
    expect(Module::isDisabled('EnabledModule'))->toBeFalse();

    expect(Module::isDisabled('DisabledModule'))->toBeTrue();
    expect(Module::isEnabled('DisabledModule'))->toBeFalse();
});

it('enables a module and fires event', function (): void {
    Event::fake();
    createFakeModule('ToEnableModule', [], false);

    Module::enable('ToEnableModule');

    expect(Module::isEnabled('ToEnableModule'))->toBeTrue();
    Event::assertDispatched(ModuleEnabled::class, fn ($e): bool => $e->module === 'ToEnableModule');
});

it('fails to enable module with unfulfilled requirements', function (): void {
    createFakeModule('RequiresModule', ['requiredModules' => ['missing-module']], false);

    expect(fn () => Module::enable('RequiresModule'))->toThrow(Exception::class);
});

it('disables a module and its dependents and fires event', function (): void {
    Event::fake();
    createFakeModule('BaseModule', ['id' => 'base-module'], true);
    createFakeModule('DependentModule', ['id' => 'dependent-module', 'requiredModules' => ['base-module']], true);

    Module::disable('BaseModule');

    expect(Module::isDisabled('BaseModule'))->toBeTrue();
    expect(Module::isDisabled('DependentModule'))->toBeTrue();

    Event::assertDispatched(ModuleDisabled::class, fn ($e): bool => $e->module === 'BaseModule');
    Event::assertDispatched(ModuleDisabled::class, fn ($e): bool => $e->module === 'DependentModule');
});

it('gets module prefix', function (): void {
    expect(Module::prefix('TestModule'))->toBe('test-module');
});

it('gets module namespace', function (): void {
    expect(Module::namespace('TestModule'))->toBe('Modules\\TestModule');
});

it('gets providers for enabled modules', function (): void {
    $path = createFakeModule('ProviderModule', [], true, ['App' . DIRECTORY_SEPARATOR . 'Providers']);

    file_put_contents($path . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . 'TestServiceProvider.php', '<?php');

    $providers = Module::providers();
    expect($providers)->toContain('Modules\\ProviderModule\\App\\Providers\\TestServiceProvider');
});

it('gets seeders for enabled modules', function (): void {
    $path = createFakeModule('SeederModule', [], true, ['Database' . DIRECTORY_SEPARATOR . 'Seeders']);

    file_put_contents($path . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders' . DIRECTORY_SEPARATOR . 'DatabaseSeeder.php', '<?php');

    $seeders = Module::seeders();
    expect($seeders)->toContain('Modules\\SeederModule\\Database\\Seeders\\DatabaseSeeder');
});

it('gets path for specific folder', function (): void {
    $expectedPath = config('core.module_path', Module::MODULES_PATH) . DIRECTORY_SEPARATOR . 'TestModule';
    $path = Module::pathFor('TestModule', 'controllers'); // unregistered folder returns root path
    expect($path)->toBe(base_path($expectedPath));

    $mappings = [
        'app' => 'App',
        'livewire' => 'App' . DIRECTORY_SEPARATOR . 'Livewire',
        'components' => 'App' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components',
        'components-view' => 'Resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'components',
        'config' => 'Config',
        'database' => 'Database',
        'migrations' => 'Database' . DIRECTORY_SEPARATOR . 'Migrations',
        'factories' => 'Database' . DIRECTORY_SEPARATOR . 'Factories',
        'seeders' => 'Database' . DIRECTORY_SEPARATOR . 'Seeders',
        'lang' => 'Lang',
        'resources' => 'Resources',
        'routes' => 'Routes',
        'views' => 'Resources' . DIRECTORY_SEPARATOR . 'views',
    ];

    foreach ($mappings as $for => $mapped) {
        $expected = base_path($expectedPath . DIRECTORY_SEPARATOR . $mapped);
        expect(Module::pathFor('TestModule', $for))->toBe($expected);
    }
});

it('gets module info and collection', function (): void {
    createFakeModule('InfoModule', ['id' => 'info-module']);

    $info = Module::info('InfoModule');
    expect($info->name)->toBe('InfoModule')
        ->and($info->id)->toBe('info-module')
        ->and($info->enabled)->toBeFalse();

    $modules = Module::get();
    expect($modules)->toHaveCount(1);
    expect($modules->first()->name)->toBe('InfoModule');
});

it('checks registration for app', function (): void {
    createFakeModule('RegModule', ['id' => 'reg-module', 'version' => '1.0']);
    config(['core.url.server' => 'https://devanox.test', 'app.id' => 'test-app', 'app.version' => '1.0.0']);

    Http::fake([
        '*/api/module/is-registered-for-app' => Http::response(['status' => 'success'], 200),
    ]);

    $result = Module::isRegisterForApp('RegModule');
    expect($result)->toBeObject()
        ->and($result->status)->toBe('success');
});

it('returns null when checking registration for app without id', function (): void {
    createFakeModule('NoIdModule', []);
    expect(Module::isRegisterForApp('NoIdModule'))->toBeNull();
});

it('returns null when server url is not set', function (): void {
    createFakeModule('RegModule', ['id' => 'reg-module']);
    config(['core.url.server' => null]);
    expect(Module::isRegisterForApp('RegModule'))->toBeNull();
});

it('returns null when http request fails', function (): void {
    createFakeModule('RegModule', ['id' => 'reg-module']);
    config(['core.url.server' => 'https://devanox.test']);

    Http::fake([
        '*/api/module/is-registered-for-app' => Http::response([], 500),
    ]);

    expect(Module::isRegisterForApp('RegModule'))->toBeNull();
});

it('returns null on http exception', function (): void {
    createFakeModule('RegModule', ['id' => 'reg-module']);
    config(['core.url.server' => 'https://devanox.test']);

    Http::fake([
        '*/api/module/is-registered-for-app' => function (): void {
            throw new Exception('Connection error');
        },
    ]);

    expect(Module::isRegisterForApp('RegModule'))->toBeNull();
});
