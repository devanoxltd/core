<?php

declare(strict_types=1);

use Devanox\Core\Http\Middleware\InstallApp;
use Devanox\Core\Http\Middleware\License as LicenseMiddleware;
use Devanox\Core\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function withNonTestingEnvironment(callable $callback): void
{
    $originalEnv = resolve('env');
    app()->instance('env', 'production');

    try {
        $callback();
    } finally {
        app()->instance('env', $originalEnv);
    }
}

function installedFilePath(): string
{
    return storage_path('installed');
}

function markAppInstalled(): void
{
    $path = installedFilePath();

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0o755, true);
    }

    touch($path);
}

function markAppNotInstalled(): void
{
    $path = installedFilePath();

    if (is_file($path)) {
        unlink($path);
    }
}

beforeEach(function (): void {
    markAppNotInstalled();
    License::query()->delete();
});

afterEach(function (): void {
    markAppNotInstalled();
    License::query()->delete();
});

it('lets requests through when app is installed', function (): void {
    markAppInstalled();

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/');
        $response = (new InstallApp)->handle($request, fn (): Response => new Response('OK'));

        expect($response->getContent())->toBe('OK');
    });
});

it('redirects to install route when app is not installed', function (): void {
    withNonTestingEnvironment(function (): void {
        $request = Request::create('/');
        $response = (new InstallApp)->handle($request, fn (): Response => new Response('OK'));

        expect($response->isRedirect())->toBeTrue()
            ->and($response->headers->get('Location'))->toContain('install');
    });
});

it('returns json forbidden when api request and app is not installed', function (): void {
    withNonTestingEnvironment(function (): void {
        $request = Request::create('/api/data', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = (new InstallApp)->handle($request, fn (): Response => new Response('OK'));

        expect($response->getStatusCode())->toBe(403)
            ->and($response->getContent())->toContain(trans('core::install.not_installed'));
    });
});

it('allows install route when app is not installed', function (): void {
    withNonTestingEnvironment(function (): void {
        $request = Request::create('/install');
        $response = (new InstallApp)->handle($request, fn (): Response => new Response('install page'));

        expect($response->getContent())->toBe('install page');
    });
});

it('uses fallback routes when config route_allow is empty', function (): void {
    config(['core.route_allow' => []]);

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/livewire-fake/test');
        $response = (new InstallApp)->handle($request, fn (): Response => new Response('livewire page'));

        expect($response->getContent())->toBe('livewire page');
    });
});

it('redirects to license route when app is installed but not licensed', function (): void {
    markAppInstalled();

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/');
        $response = (new LicenseMiddleware)->handle($request, fn (): Response => new Response('OK'));

        expect($response->isRedirect())->toBeTrue()
            ->and($response->headers->get('Location'))->toContain('license');
    });
});

it('lets requests through when license is valid', function (): void {
    markAppInstalled();
    License::query()->create(['key' => 'valid-license', 'status' => 'valid', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/');
        $response = (new LicenseMiddleware)->handle($request, fn (): Response => new Response('OK'));

        expect($response->getContent())->toBe('OK');
    });
});

it('redirects to license route when installed but license invalid', function (): void {
    markAppInstalled();
    License::query()->create(['key' => 'invalid-license', 'status' => 'invalid', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/');
        $response = (new LicenseMiddleware)->handle($request, fn (): Response => new Response('OK'));

        expect($response->isRedirect())->toBeTrue()
            ->and($response->headers->get('Location'))->toContain('license');
    });
});

it('returns json forbidden when api request and license invalid', function (): void {
    markAppInstalled();
    License::query()->create(['key' => 'invalid-license', 'status' => 'invalid', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/api/data', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = (new LicenseMiddleware)->handle($request, fn (): Response => new Response('OK'));

        expect($response->getStatusCode())->toBe(403)
            ->and($response->getContent())->toContain(trans('core::install.not_activated'));
    });
});

it('allows license route when installed but license invalid', function (): void {
    markAppInstalled();
    License::query()->create(['key' => 'invalid-license', 'status' => 'invalid', 'purchase_at' => now(), 'support_until' => now()->addYear()]);

    withNonTestingEnvironment(function (): void {
        $request = Request::create('/license');
        $response = (new LicenseMiddleware)->handle($request, fn (): Response => new Response('license page'));

        expect($response->getContent())->toBe('license page');
    });
});
