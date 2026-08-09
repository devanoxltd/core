<?php

declare(strict_types=1);

use Devanox\Core\Http\Middleware\PreventAccessFromCentralDomains;
use Devanox\Core\Models\Tenant;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Devanox\Core\Helpers\tenancy;

it('aborts when accessed from central domain', function (): void {
    Config::set('tenancy.enabled', true);
    // When tenancy is enabled, but no tenant is set, tenant() returns null.

    $middleware = new PreventAccessFromCentralDomains;
    $request = Request::create('/test');

    expect(fn (): Response => $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK')))
        ->toThrow(HttpException::class); // 404 abort
});

it('proceeds when tenant is present', function (): void {
    Config::set('tenancy.enabled', true);

    $tenant = Tenant::withoutEvents(fn () => Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => 'active',
        'config' => ['database' => ['database' => 'tenant_1', 'driver' => 'mysql']],
    ]));
    tenancy()->setTenant($tenant);

    $middleware = new PreventAccessFromCentralDomains;
    $request = Request::create('/test');

    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->getContent())->toBe('OK');

    tenancy()->unsetTenant();
});
