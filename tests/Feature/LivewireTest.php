<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Devanox\Core\Models\License;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->installedFile = storage_path('installed');

    if (file_exists($this->installedFile)) {
        unlink($this->installedFile);
    }

    $this->installFile = InstallerInfo::filePath();

    if (File::exists($this->installFile)) {
        File::delete($this->installFile);
    }

    License::query()->delete();
    Cache::flush();
});

afterEach(function (): void {
    if (file_exists($this->installedFile)) {
        unlink($this->installedFile);
    }

    if (File::exists($this->installFile)) {
        File::delete($this->installFile);
    }
});

it('renders install livewire component', function (): void {
    $this->get('/install')->assertOk()->assertSee('Devanox Installer');

    Livewire::test('core::install')
        ->assertSet('activeStep', 'home')
        ->assertSet('nextStep', 'requirements')
        ->assertSeeHtml('wire:click="goToStep(\'requirements\')"');
});

it('can navigate steps in install component', function (): void {
    Livewire::test('core::install')
        ->call('goToStep', 'requirements')
        ->assertSet('activeStep', 'requirements')
        ->assertSet('nextStep', null)
        ->dispatch('stepReady', step: 'requirements')
        ->assertSet('nextStep', 'permissions')
        ->dispatch('unsetNextStep')
        ->assertSet('nextStep', null);
});

it('can finish installation', function (): void {
    config(['app.version' => '1.0.0']);
    $this->artisan('config:clear');

    Livewire::test('core::install')
        ->call('finish')
        ->assertRedirect(route('login'));

    expect(file_exists(storage_path('installed')))->toBeTrue();
    expect(InstallerInfo::getStatus())->toBe(InstallerInfo::COMPLETED);
});

it('renders license livewire component', function (): void {
    $this->get('/license')->assertOk()->assertSee('License Activation');
});

it('activates license via livewire component', function (): void {
    config(['core.url.server' => 'https://devanox.test']);

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

    Livewire::test('core::license')
        ->set('licenseKey', 'valid-key')
        ->call('activate')
        ->assertHasNoErrors();

    expect(License::query()->where('key', 'license-id')->exists())->toBeTrue();
});

it('shows error on invalid license activation', function (): void {
    config(['core.url.server' => 'https://devanox.test']);

    Http::fake([
        '*' => Http::response(json_encode([
            'status' => 'error',
            'message' => 'Invalid license key format',
        ]), 400, ['Content-Type' => 'application/json']),
    ]);

    Livewire::test('core::license')
        ->set('licenseKey', 'invalid-key')
        ->call('activate')
        ->assertHasErrors(['licenseKey'])
        ->assertSee('Invalid license key format');
});

it('redirects from license component if already activated', function (): void {
    License::query()->create(['key' => 'valid-license', 'status' => 'valid', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    // We mock the cache since isLicenseValid checks cache, but since we created it, it might not be cached.
    // Actually the helper uses License::isValidLicense which checks db if not cached.
    Livewire::test('core::license')
        ->assertRedirect(route('login'));
});
