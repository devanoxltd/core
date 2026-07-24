<?php

declare(strict_types=1);

use Devanox\Core\Helpers\App;
use Devanox\Core\Models\License;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('core.url.server', 'https://devanox-activate.test');
});

it('throws exception if server url is empty', function (): void {
    Config::set('core.url.server');

    expect(fn (): array => App::verifyLicense('valid-key'))
        ->toThrow(Exception::class, __('core::app.exception.server_url'));

    Config::set('core.url.server', '');
    expect(fn (): array => App::verifyLicense('valid-key'))
        ->toThrow(Exception::class, __('core::app.exception.server_url'));
});

it('throws exception on http failure with json message', function (): void {
    Http::fake([
        '*' => Http::response(json_encode(['message' => 'Invalid license key format']), 400, ['Content-Type' => 'application/json']),
    ]);

    expect(fn (): array => App::verifyLicense('invalid-key'))
        ->toThrow(Exception::class, 'Invalid license key format');
});

it('throws generic exception on http failure without json message', function (): void {
    Http::fake([
        '*' => Http::response('Server error', 500),
    ]);

    expect(fn (): array => App::verifyLicense('key'))
        ->toThrow(Exception::class, __('core::app.exception.error'));
});

it('throws exception on invalid response data', function (): void {
    Http::fake([
        '*' => Http::response(json_encode(['status' => 'error', 'message' => 'Not found']), 200, ['Content-Type' => 'application/json']),
    ]);

    expect(fn (): array => App::verifyLicense('key'))
        ->toThrow(Exception::class, 'Not found');
});

it('throws exception on empty response data', function (): void {
    Http::fake([
        '*' => Http::response(null, 200), // empty response
    ]);
    expect(fn (): array => App::verifyLicense('key'))
        ->toThrow(Exception::class, __('core::app.exception.error'));
});

it('verifies core license successfully', function (): void {
    Http::fake([
        '*' => Http::response(json_encode([
            'status' => 'success',
            'data' => [
                'id' => 'lic-123',
                'purchase_code' => 'pc-123',
                'type' => 'extended',
                'purchase_at' => '2025-01-01',
                'status' => 'valid',
            ],
        ]), 200, ['Content-Type' => 'application/json']),
    ]);

    $result = App::verifyLicense('lic-123');

    expect($result)->toBeArray()->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(License::class);
    expect($result[0]->key)->toBe('lic-123');
    expect($result[0]->type)->toBe('extended');
    expect($result[0]->is_module)->toBeFalse();
    expect($result[0]->status)->toBe('valid');
});

it('verifies module license successfully', function (): void {
    Http::fake([
        '*' => Http::response(json_encode([
            'status' => 'success',
            'data' => [
                'id' => 'lic-module-123',
                'module' => [
                    'name' => 'AwesomeModule',
                ],
                'status' => 'valid',
            ],
        ]), 200, ['Content-Type' => 'application/json']),
    ]);

    $result = App::verifyLicense('lic-module-123', 1);

    expect($result)->toBeArray()->toHaveCount(2);
    expect($result[0]->key)->toBe('lic-module-123');
    expect($result[0]->is_module)->toBeTrue();
    expect($result[0]->module_name)->toBe('AwesomeModule');
});
