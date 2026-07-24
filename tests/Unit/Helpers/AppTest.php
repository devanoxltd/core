<?php

declare(strict_types=1);

use Devanox\Core\Helpers\App;
use Devanox\Core\Models\License;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    License::query()->delete();
    config(['core.url.server' => 'https://devanox.test']);
    config(['app.url' => 'https://example.com']);
    config(['app.id' => 'app-id']);
    config(['app.version' => '1.0.0']);
});

function fakeVerifyResponse(array $body, int $status = 200): void
{
    Http::fake(fn () => Http::response(json_encode($body), $status, ['Content-Type' => 'application/json']));
}

it('throws exception when server url is missing', function (): void {
    config(['core.url.server' => null]);

    expect(fn (): array => App::verifyLicense('key'))->toThrow(Exception::class, 'Server URL is not configured');
});

it('throws exception when server url is empty', function (): void {
    config(['core.url.server' => '   ']);

    expect(fn (): array => App::verifyLicense('key'))->toThrow(Exception::class, 'Server URL is not configured');
});

it('throws exception on failed verification response', function (): void {
    fakeVerifyResponse(['message' => 'Invalid license'], 422);

    expect(fn (): array => App::verifyLicense('key'))->toThrow(Exception::class, 'Invalid license');
});

it('throws exception on unsuccessful status response', function (): void {
    fakeVerifyResponse(['status' => 'error', 'message' => 'Bad request'], 200);

    expect(fn (): array => App::verifyLicense('key'))->toThrow(Exception::class, 'Bad request');
});

it('throws exception when response data is empty', function (): void {
    fakeVerifyResponse(['status' => 'success', 'data' => []], 200);

    expect(fn (): array => App::verifyLicense('key'))->toThrow(Exception::class);
});

it('creates a core license on successful verification', function (): void {
    fakeVerifyResponse([
        'status' => 'success',
        'data' => [
            'id' => 'license-id',
            'purchase_code' => 'PC123',
            'type' => 'extended',
            'purchase_at' => '2025-01-01 00:00:00',
            'support_until' => '2026-01-01 00:00:00',
            'status' => 'valid',
        ],
    ]);

    [$license, $response] = App::verifyLicense('key');

    expect($license)->toBeInstanceOf(License::class)
        ->and($license->key)->toBe('license-id')
        ->and($license->is_module)->toBeFalse()
        ->and($response->status)->toBe('success');
});

it('creates a module license on successful verification', function (): void {
    fakeVerifyResponse([
        'status' => 'success',
        'data' => [
            'id' => 'module-license-id',
            'purchase_code' => 'PC456',
            'type' => 'regular',
            'purchase_at' => '2025-01-01 00:00:00',
            'support_until' => '2026-01-01 00:00:00',
            'status' => 'valid',
            'module' => ['name' => 'TestModule'],
        ],
    ]);

    [$license, $response] = App::verifyLicense('key', 123);

    expect($license)->toBeInstanceOf(License::class)
        ->and($license->key)->toBe('module-license-id')
        ->and($license->is_module)->toBeTrue()
        ->and($license->module_name)->toBe('TestModule')
        ->and($response->status)->toBe('success');
});
