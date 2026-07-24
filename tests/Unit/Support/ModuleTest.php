<?php

declare(strict_types=1);

use Devanox\Core\Events\ModuleDisabled;
use Devanox\Core\Events\ModuleEnabled;
use Devanox\Core\Support\Module;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    cleanModulesDirectory();
});

afterEach(function (): void {
    cleanModulesDirectory();
});

it('lists all module directories', function (): void {
    createFakeModule('Alpha', ['id' => 'alpha']);
    createFakeModule('Beta', ['id' => 'beta']);

    expect(Module::all())->toContain('Alpha', 'Beta');
});

it('builds module path', function (): void {
    $modulePath = config('core.module_path', 'modules');

    expect(Module::path('Alpha'))->toBe($modulePath . DIRECTORY_SEPARATOR . 'Alpha')
        ->and(Module::path('Alpha', true))->toBe(base_path($modulePath . DIRECTORY_SEPARATOR . 'Alpha'))
        ->and(Module::path('Alpha', false, true))->toBe($modulePath . DIRECTORY_SEPARATOR . 'Alpha' . DIRECTORY_SEPARATOR . 'enable');
});

it('checks module existence', function (): void {
    createFakeModule('Exists', ['id' => 'exists']);

    expect(Module::exist('Exists'))->toBeTrue()
        ->and(Module::exist('Missing'))->toBeFalse();
});

it('checks enabled and disabled state', function (): void {
    createFakeModule('Enabled', ['id' => 'enabled'], true);
    createFakeModule('Disabled', ['id' => 'disabled'], false);

    expect(Module::isEnabled('Enabled'))->toBeTrue()
        ->and(Module::isDisabled('Disabled'))->toBeTrue()
        ->and(Module::isEnabled('Disabled'))->toBeFalse();
});

it('returns module info', function (): void {
    createFakeModule('InfoMod', ['id' => 'info-id', 'version' => '1.2.3'], true);

    $info = Module::info('InfoMod');

    expect($info->id)->toBe('info-id')
        ->and($info->name)->toBe('InfoMod')
        ->and($info->prefix)->toBe('info-mod')
        ->and($info->enabled)->toBeTrue()
        ->and($info->is_valid)->toBeTrue()
        ->and($info->namespace)->toBe('Modules\\InfoMod');
});

it('marks module invalid without id', function (): void {
    createFakeModule('Invalid', ['version' => '1.0.0']);

    expect(Module::isValid('Invalid'))->toBeFalse()
        ->and(Module::info('Invalid')->is_valid)->toBeFalse();
});

it('gets module config with caching', function (): void {
    createFakeModule('ConfigMod', ['id' => 'config-id', 'key' => 'value']);

    $config = Module::config('ConfigMod');

    expect($config['id'])->toBe('config-id')
        ->and($config['key'])->toBe('value');

    Module::clearCache();

    expect(Module::config('ConfigMod'))->toBe($config);
});

it('returns empty config for missing module', function (): void {
    expect(Module::config('Missing'))->toBe([]);
});

it('clears module cache', function (): void {
    createFakeModule('CacheMod', ['id' => 'cache-id']);

    $before = Module::get()->count();

    createFakeModule('CacheMod2', ['id' => 'cache-id-2']);
    Module::clearCache();

    expect(Module::get())->toHaveCount($before + 1);
});

it('enables a module', function (): void {
    Event::fake([ModuleEnabled::class]);

    createFakeModule('EnableMe', ['id' => 'enable-me'], false);

    Module::enable('EnableMe');

    expect(Module::isEnabled('EnableMe'))->toBeTrue();
    Event::assertDispatched(ModuleEnabled::class, fn (ModuleEnabled $event): bool => $event->module === 'EnableMe');
});

it('disables a module and dependents', function (): void {
    Event::fake([ModuleDisabled::class]);

    createFakeModule('Parent', ['id' => 'parent-id'], true);
    createFakeModule('Child', ['id' => 'child-id', 'requiredModules' => ['parent-id']], true);

    Module::disable('Parent');

    expect(Module::isEnabled('Parent'))->toBeFalse()
        ->and(Module::isEnabled('Child'))->toBeFalse();

    Event::assertDispatched(ModuleDisabled::class, fn (ModuleDisabled $event): bool => $event->module === 'Parent');
    Event::assertDispatched(ModuleDisabled::class, fn (ModuleDisabled $event): bool => $event->module === 'Child');
});

it('throws when enabling module with unmet requirements', function (): void {
    createFakeModule('Needy', ['id' => 'needy-id', 'requiredModules' => ['missing-id']], false);

    expect(fn () => Module::enable('Needy'))->toThrow(Exception::class);
});

it('checks requirements are fulfilled', function (): void {
    createFakeModule('Dep', ['id' => 'dep-id'], true);
    createFakeModule('Needy', ['id' => 'needy-id', 'requiredModules' => ['dep-id']], false);

    expect(Module::isRequirementsFullFill('Needy'))->toBeTrue();
});

it('checks requirements are not fulfilled', function (): void {
    createFakeModule('Needy', ['id' => 'needy-id', 'requiredModules' => ['missing-id']], false);

    expect(Module::isRequirementsFullFill('Needy'))->toBeFalse();
});

it('returns module providers', function (): void {
    createFakeModule('ProviderMod', ['id' => 'provider-id'], true, [
        'App' . DIRECTORY_SEPARATOR . 'Providers',
    ]);

    $providerPath = Module::pathFor('ProviderMod', 'app') . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . 'TestServiceProvider.php';
    File::ensureDirectoryExists(dirname($providerPath));
    File::put($providerPath, "<?php\nnamespace Modules\\ProviderMod\\App\\Providers;\nclass TestServiceProvider {}\n");

    expect(Module::providers())->toContain('Modules\\ProviderMod\\App\\Providers\\TestServiceProvider');
});

it('returns module seeders', function (): void {
    createFakeModule('SeederMod', ['id' => 'seeder-id'], true, [
        'Database' . DIRECTORY_SEPARATOR . 'Seeders',
    ]);

    $seederPath = Module::pathFor('SeederMod', 'seeders') . DIRECTORY_SEPARATOR . 'DatabaseSeeder.php';
    File::ensureDirectoryExists(dirname($seederPath));
    File::put($seederPath, "<?php\nnamespace Modules\\SeederMod\\Database\\Seeders;\nclass DatabaseSeeder {}\n");

    expect(Module::seeders())->toContain('Modules\\SeederMod\\Database\\Seeders\\DatabaseSeeder');
});

it('returns correct path for components', function (): void {
    expect(Module::forPath('components'))->toContain('App' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Components')
        ->and(Module::forPath('migrations'))->toContain('Database' . DIRECTORY_SEPARATOR . 'Migrations')
        ->and(Module::forPath('views'))->toContain('Resources' . DIRECTORY_SEPARATOR . 'views')
        ->and(Module::forPath('unknown'))->toBe('');
});

it('checks app registration with server', function (): void {
    config(['core.url.server' => 'https://devanox.test']);

    Http::fake(fn () => Http::response(json_encode(['registered' => true]), 200, ['Content-Type' => 'application/json']));

    createFakeModule('RegMod', ['id' => 'reg-id', 'version' => '1.0.0'], true);

    $result = Module::isRegisterForApp('RegMod');

    expect($result)->toBeObject()
        ->and($result->registered)->toBeTrue();
});

it('returns null when registration server fails', function (): void {
    config(['core.url.server' => 'https://devanox.test']);

    Http::fake(fn () => Http::response(null, 500));

    createFakeModule('RegMod', ['id' => 'reg-id'], true);

    expect(Module::isRegisterForApp('RegMod'))->toBeNull();
});

it('returns null registration check without server url', function (): void {
    config(['core.url.server' => null]);

    createFakeModule('RegMod', ['id' => 'reg-id'], true);

    expect(Module::isRegisterForApp('RegMod'))->toBeNull();
});

it('returns null registration check for invalid module', function (): void {
    config(['core.url.server' => 'https://devanox.test']);

    createFakeModule('InvalidReg', ['version' => '1.0.0'], true);

    expect(Module::isRegisterForApp('InvalidReg'))->toBeNull();
});
