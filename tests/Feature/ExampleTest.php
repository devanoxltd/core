<?php

declare(strict_types=1);

use Devanox\Core\Core;

it('resolves the singleton', function (): void {
    expect(resolve(Core::class))->toBeInstanceOf(Core::class);
});

it('returns the same instance from the container', function (): void {
    expect(resolve(Core::class))->toBe(resolve(Core::class));
});

it('merges the package config', function (): void {
    expect(config('core.placeholder'))->toBe('default');
});

it('loads the package translations', function (): void {
    expect(trans('core::messages.placeholder'))->toBe('Core placeholder translation.');
});

it('loads the package views', function (): void {
    expect(view()->exists('core::placeholder'))->toBeTrue();
});

it('registers the artisan command', function (): void {
    $this->artisan('core:placeholder')
        ->expectsOutputToContain('Core placeholder command executed.')
        ->assertSuccessful();
});
