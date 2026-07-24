<?php

declare(strict_types=1);

use Devanox\Core\Models\License;
use Devanox\Core\Support\Module;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    cleanModulesDirectory();
    License::query()->delete();
    Cache::flush();
});

afterEach(function (): void {
    cleanModulesDirectory();
    Cache::flush();

    $installedFile = storage_path('installed');

    if (is_file($installedFile)) {
        unlink($installedFile);
    }
});

it('detects app is not installed', function (): void {
    expect(isAppInstalled())->toBeFalse();
});

it('detects app is installed', function (): void {
    file_put_contents(storage_path('installed'), '');

    expect(isAppInstalled())->toBeTrue();
});

it('returns all modules', function (): void {
    $before = modules(null)->count();

    createFakeModule('AllAlpha', ['id' => 'all-alpha']);
    createFakeModule('AllBeta', ['id' => 'all-beta']);
    Module::clearCache();

    expect(modules(null))->toHaveCount($before + 2);
});

it('returns enabled modules only', function (): void {
    $before = modules(true)->count();

    createFakeModule('EnabledAlpha', ['id' => 'enabled-alpha'], true);
    createFakeModule('DisabledAlpha', ['id' => 'disabled-alpha'], false);
    Module::clearCache();

    $enabled = modules(true);

    expect($enabled)->toHaveCount($before + 1)
        ->and($enabled->firstWhere('name', 'EnabledAlpha'))->not->toBeNull();
});

it('returns disabled modules only', function (): void {
    $before = modules(false)->count();

    createFakeModule('EnabledBeta', ['id' => 'enabled-beta'], true);
    createFakeModule('DisabledBeta', ['id' => 'disabled-beta'], false);
    Module::clearCache();

    $disabled = modules(false);

    expect($disabled)->toHaveCount($before + 1)
        ->and($disabled->firstWhere('name', 'DisabledBeta'))->not->toBeNull();
});

it('checks license validity with caching', function (): void {
    License::query()->create([
        'key' => 'core-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'is_module' => false,
    ]);

    expect(isLicenseValid())->toBeTrue();
    expect(Cache::has('license.valid.core'))->toBeTrue();
});

it('returns cached license validity result', function (): void {
    Cache::put('license.valid.core', false, now()->addMinutes(30));

    expect(isLicenseValid())->toBeFalse();
});

it('checks module license validity', function (): void {
    License::query()->create([
        'key' => 'module-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'is_module' => true,
        'module_name' => 'TestModule',
    ]);

    expect(isLicenseValid('TestModule'))->toBeTrue()
        ->and(isLicenseValid('MissingModule'))->toBeFalse();
});
