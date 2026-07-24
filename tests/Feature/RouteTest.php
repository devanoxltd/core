<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->installedFile = storage_path('installed');

    if (file_exists($this->installedFile)) {
        unlink($this->installedFile);
    }
});

afterEach(function (): void {
    if (file_exists($this->installedFile)) {
        unlink($this->installedFile);
    }
});

it('registers install route when not installed', function (): void {
    expect(Route::has('devanox.install'))->toBeTrue();

    $this->get('/install')->assertOk()->assertViewIs('core::install');
});

it('registers license route', function (): void {
    expect(Route::has('devanox.license'))->toBeTrue();

    $this->get('/license')->assertOk()->assertViewIs('core::license');
});

it('loads workbench welcome page', function (): void {
    $this->get('/')->assertOk()->assertSee('Core Workbench');
});
