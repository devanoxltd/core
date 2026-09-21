<?php

declare(strict_types=1);

use Devanox\Core\Models\License;
use Livewire\Livewire;

it('renders about livewire component', function (): void {
    Livewire::test('core::about')
        ->assertOk();
});

it('shows system information', function (): void {
    Livewire::test('core::about')
        ->assertSee(config('app.version'))
        ->assertSee(PHP_VERSION)
        ->assertSee(app()->version());
});

it('shows license information if available', function (): void {
    $license = License::query()->create([
        'key' => 'test-license-key',
        'purchase_code' => 'test-purchase-code',
        'type' => 'regular',
        'purchase_at' => now(),
        'support_until' => now()->addYear(),
        'status' => 'valid',
        'is_core' => true,
    ]);

    Livewire::test('core::about')
        ->assertSee('test-license-key')
        ->assertSee('test-purchase-code')
        ->assertSee('regular');
});
