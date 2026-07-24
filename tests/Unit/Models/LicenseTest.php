<?php

declare(strict_types=1);

use Devanox\Core\Models\License;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    License::query()->delete();
});

it('validates a core license', function (): void {
    $license = new License([
        'key' => 'valid-core-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'is_module' => false,
    ]);

    expect($license->isValid())->toBeTrue()
        ->and($license->hasActiveSupport())->toBeTrue();
});

it('invalidates a license with missing key', function (): void {
    $license = new License([
        'key' => '',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
    ]);

    expect($license->isValid())->toBeFalse();
});

it('invalidates a license with invalid status', function (): void {
    $license = new License([
        'key' => 'invalid-key',
        'status' => 'invalid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
    ]);

    expect($license->isValid())->toBeFalse();
});

it('invalidates a license without purchase date', function (): void {
    $license = new License([
        'key' => 'no-purchase-key',
        'status' => 'valid',
        'purchase_at' => null,
        'support_until' => now()->addYear(),
    ]);

    expect($license->isValid())->toBeFalse();
});

it('invalidates a license without support date', function (): void {
    $license = new License([
        'key' => 'no-support-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => null,
    ]);

    expect($license->isValid())->toBeFalse();
});

it('detects expired support', function (): void {
    $license = new License([
        'key' => 'expired-key',
        'status' => 'valid',
        'purchase_at' => now()->subYears(2),
        'support_until' => now()->subDay(),
    ]);

    expect($license->isValid())->toBeTrue()
        ->and($license->hasActiveSupport())->toBeFalse();
});

it('scopes query to core licenses', function (): void {
    License::query()->create([
        'key' => 'core-key',
        'is_module' => false,
    ]);

    License::query()->create([
        'key' => 'module-key',
        'is_module' => true,
        'module_name' => 'TestModule',
    ]);

    expect(License::query()->isCore()->count())->toBe(1)
        ->and(License::query()->isCore()->first()?->key)->toBe('core-key');
});

it('scopes query to module licenses', function (): void {
    License::query()->create([
        'key' => 'core-key',
        'is_module' => false,
    ]);

    License::query()->create([
        'key' => 'module-key',
        'is_module' => true,
        'module_name' => 'TestModule',
    ]);

    expect(License::query()->isModule('TestModule')->count())->toBe(1)
        ->and(License::query()->isModule('TestModule')->first()?->key)->toBe('module-key');
});

it('checks static valid license helper for core', function (): void {
    License::query()->create([
        'key' => 'core-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'is_module' => false,
    ]);

    expect(License::isValidLicense())->toBeTrue();
});

it('checks static valid license helper for module', function (): void {
    License::query()->create([
        'key' => 'module-key',
        'status' => 'valid',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'is_module' => true,
        'module_name' => 'TestModule',
    ]);

    expect(License::isValidLicense('TestModule'))->toBeTrue()
        ->and(License::isValidLicense('MissingModule'))->toBeFalse();
});

it('casts boolean and datetime attributes', function (): void {
    $license = License::query()->create([
        'key' => 'cast-key',
        'is_module' => '1',
        'update_notification' => '0',
        'purchase_at' => '2025-01-01 00:00:00',
        'support_until' => '2025-12-31 23:59:59',
    ]);

    expect($license->is_module)->toBeTrue()
        ->and($license->update_notification)->toBeFalse()
        ->and($license->purchase_at)->toBeInstanceOf(Carbon::class)
        ->and($license->support_until)->toBeInstanceOf(Carbon::class);
});
