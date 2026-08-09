<?php

declare(strict_types=1);

use Devanox\Core\Models\Domain;
use Devanox\Core\Models\Tenant;
use Devanox\Core\Support\Tenancy;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    Tenant::query()->forceDelete();

    $this->tenant = Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => 'active',
        'config' => [
            'database' => [
                'database' => ':memory:',
                'driver' => 'sqlite',
            ],
            'custom_key' => 'custom_value',
        ],
    ]);

    $this->domain = $this->tenant->domains()->create([
        'domain' => 't1',
        'status' => 'active',
    ]);
});

afterEach(function (): void {
    (new Tenancy)->unsetTenant();
    Mockery::close();
});

it('instantiates empty tenancy when request domain is app domain', function (): void {
    Config::set('app.domain', 'localhost');
    Config::set('tenancy.central_domains', ['localhost']);

    $request = request();
    $request->headers->set('HOST', 'localhost');

    $tenancy = new Tenancy;

    expect($tenancy->tenant)->toBeNull();
});

it('returns setting value from tenant', function (): void {
    $tenancy = new Tenancy;
    $tenancy->tenant = $this->tenant;

    expect($tenancy->get('name'))->toBe('Tenant 1')
        ->and($tenancy->get('missing_key', 'default'))->toBe('default');

    $tenancy->tenant = null;
    expect($tenancy->get('name', 'fallback'))->toBe('fallback');
});

it('resolves request domain from request or config', function (): void {
    $tenancy = new Tenancy;

    $request = request();
    $request->headers->set('HOST', 'example.com');

    expect($tenancy->requestDomain())->toBe('example.com');

    // Simulate exception
    $mockRequest = Mockery::mock(Request::class)->makePartial();
    $mockRequest->shouldReceive('getHost')->andThrow(new Exception('No host'));
    app()->instance('request', $mockRequest);

    Config::set('app.domain', 'fallback.com');
    expect($tenancy->requestDomain())->toBe('fallback.com');
});

it('identifies central domains', function (): void {
    Config::set('tenancy.central_domains', ['domain1.com', 'domain2.com']);
    $tenancy = new Tenancy;

    expect($tenancy->centralDomains())->toBe(['domain1.com', 'domain2.com']);
});

it('determines app domain correctly', function (): void {
    Config::set('tenancy.central_domains', ['myapp.com']);
    Config::set('app.domain', 'myapp.com');

    $tenancy = new Tenancy;

    expect($tenancy->appDomain('tenant.myapp.com'))->toBe('myapp.com')
        ->and($tenancy->appDomain('other.com'))->toBe('myapp.com');
});

it('checks if hostname is app subdomain', function (): void {
    Config::set('tenancy.central_domains', ['myapp.com']);
    Config::set('app.domain', 'myapp.com');

    $tenancy = new Tenancy;

    expect($tenancy->isAppSubdomain('tenant.myapp.com'))->toBeTrue()
        ->and($tenancy->isAppSubdomain('myapp.com'))->toBeFalse()
        ->and($tenancy->isAppSubdomain('other.com'))->toBeFalse();
});

it('checks if hostname is app domain', function (): void {
    Config::set('tenancy.central_domains', ['myapp.com', 'localhost']);
    $tenancy = new Tenancy;

    expect($tenancy->isAppDomain('myapp.com'))->toBeTrue()
        ->and($tenancy->isAppDomain('tenant.myapp.com'))->toBeFalse();
});

it('gets tenant domain from hostname', function (): void {
    Config::set('tenancy.central_domains', ['myapp.com']);
    Config::set('app.domain', 'myapp.com');
    $tenancy = new Tenancy;

    expect($tenancy->tenantDomain('tenant.myapp.com'))->toBe('tenant')
        ->and($tenancy->tenantDomain('customdomain.com'))->toBe('customdomain.com');
});

it('gets tenant by domain', function (): void {
    Config::set('tenancy.central_domains', ['myapp.com']);
    Config::set('app.domain', 'myapp.com');
    $tenancy = new Tenancy;

    $tenant = $tenancy->getTenantByDomain('t1.myapp.com');
    expect($tenant)->not->toBeNull()
        ->and($tenant->id)->toBe(1);

    $tenant2 = $tenancy->getTenantByDomain('missing.myapp.com');
    expect($tenant2)->toBeNull();
});

it('returns null when getting tenant by central domain', function (): void {
    Config::set('tenancy.central_domains', ['myapp.com']);
    $tenancy = new Tenancy;
    expect($tenancy->getTenantByDomain('myapp.com'))->toBeNull();
});

it('sets tenant and configuration', function (): void {
    $tenancy = new Tenancy;
    $connectionName = $tenancy->setTenant($this->tenant);

    expect($connectionName)->toBe('mysql_tenant_1')
        ->and(config('database.connections.mysql_tenant_1.database'))->toBe(':memory:')
        ->and(config('database.default'))->toBe('mysql_tenant_1')
        ->and(config('cache.default'))->toBe('database_tenant_1')
        ->and(config('custom_key'))->toBe('custom_value');

    $tenancy->unsetTenant();
});

it('returns null config when tenant has no database config', function (): void {
    $tenantWithoutDb = Tenant::query()->forceCreate(['id' => 2, 'user_id' => 1, 'name' => 'T2', 'email' => 'test@example.com', 'status' => 'active']);
    $tenancy = new Tenancy;
    $connectionName = $tenancy->setTenant($tenantWithoutDb);

    expect($connectionName)->toBeNull();
});

it('sets config with fallback domain url', function (): void {
    $this->domain->delete();

    $tenancy = new Tenancy;
    request()->headers->set('HOST', 'fallback.com');
    $tenancy->setTenantConfig();

    // Because tenant has no active domains, it uses request domain
    expect(config('app.url'))->toBe('http://localhost');
});

it('initializes tenant successfully', function (): void {
    $tenancy = new Tenancy;
    $tenancy->tenant = $this->tenant;
    $tenancy->initializeTenant();

    expect(config('database.default'))->toBe('mysql_tenant_1');

    $tenancy->unsetTenant();
});

it('throws exception during initialize if no database config', function (): void {
    $tenantWithoutDb = Tenant::query()->forceCreate(['id' => 2, 'user_id' => 1, 'name' => 'T2', 'email' => 'test@example.com', 'status' => 'active']);
    $tenancy = new Tenancy;
    $tenancy->tenant = $tenantWithoutDb;

    expect(fn () => $tenancy->initializeTenant())->toThrow(Exception::class, 'Tenant `T2` database configurations not found');
});

it('throws exception if database connection fails validation', function (): void {
    $tenantInvalidDb = Tenant::query()->forceCreate([
        'id' => 3,
        'user_id' => 1,
        'name' => 'T3',
        'email' => 'test@example.com',
        'status' => 'active',
        'config' => [
            'database' => [
                'database' => '', // Empty db name should fail getDatabaseName check
                'driver' => 'sqlite',
            ],
        ],
    ]);

    $tenancy = new Tenancy;
    $tenancy->tenant = $tenantInvalidDb;

    expect(fn () => $tenancy->initializeTenant())->toThrow(Exception::class, 'Tenant `T3` database configurations not found');
});

it('unsets tenant and resets configuration', function (): void {
    Config::set('tenancy.database.central_connection', 'sqlite');

    $tenancy = new Tenancy;
    $tenancy->setTenant($this->tenant);

    expect($tenancy->tenant)->not->toBeNull();

    $tenancy->unsetTenant();

    expect($tenancy->tenant)->toBeNull()
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('cache.default'))->toBe('database')
        ->and(config('tenancy.current'))->toBeNull();
});

it('catches QueryException when getting tenant', function (): void {
    Config::set('tenancy.models.domain', FakeDomainModelForTenancyTest::class);

    $tenancy = new Tenancy;
    $tenant = $tenancy->getTenantByDomain('fail.com');
    expect($tenant)->toBeNull();
});

final class FakeDomainModelForTenancyTest extends Domain
{
    public function newQuery(): void
    {
        throw new QueryException('sqlite', 'SELECT *', [], new Exception('db error'));
    }
}

it('catches generic Exception when getting tenant', function (): void {
    Config::set('tenancy.models.domain', FakeDomainModelExceptionForTenancyTest::class);

    $tenancy = new Tenancy;

    Log::shouldReceive('error')->once();
    $tenant = $tenancy->getTenantByDomain('fail.com');
    expect($tenant)->toBeNull();
});

final class FakeDomainModelExceptionForTenancyTest extends Domain
{
    public function newQuery(): void
    {
        throw new Exception('general error');
    }
}

it('catches exception in requestDomain and falls back to config', function (): void {
    $request = Mockery::mock(Request::class)->makePartial();
    $request->shouldReceive('getHost')->andThrow(new Exception('invalid host'));
    $this->app->instance('request', $request);

    config(['app.domain' => 'fallback-domain.com']);

    $tenancy = new Tenancy;
    expect($tenancy->requestDomain())->toBe('fallback-domain.com');
});

it('tests domain helper methods', function (): void {
    config(['tenancy.central_domains' => ['example.com', 'app.localhost']]);
    config(['app.domain' => 'example.com']);

    $tenancy = new Tenancy;

    // appDomain
    expect($tenancy->appDomain('test.example.com'))->toBe('example.com')
        ->and($tenancy->appDomain('unknown.com'))->toBe('example.com');

    // isAppSubdomain
    expect($tenancy->isAppSubdomain('test.example.com'))->toBeTrue()
        ->and($tenancy->isAppSubdomain('example.com'))->toBeFalse()
        ->and($tenancy->isAppSubdomain('unknown.com'))->toBeFalse();

    // isAppDomain
    expect($tenancy->isAppDomain('example.com'))->toBeTrue()
        ->and($tenancy->isAppDomain('unknown.com'))->toBeFalse();

    // tenantDomain
    expect($tenancy->tenantDomain('test.example.com'))->toBe('test')
        ->and($tenancy->tenantDomain('custom-domain.com'))->toBe('custom-domain.com');

    // test with null/default hostname
    $request = Mockery::mock(Request::class)->makePartial();
    $request->shouldReceive('getHost')->andReturn('test.example.com');
    app()->instance('request', $request);

    expect($tenancy->isAppSubdomain())->toBeTrue()
        ->and($tenancy->isAppDomain())->toBeFalse()
        ->and($tenancy->appDomain())->toBe('example.com');
});

it('tests tenancy helper file can be required multiple times', function (): void {
    // This will hit the false branch of function_exists in the helper file
    require __DIR__ . '/../../../app/Helpers/tenancy.php';

    expect(function_exists('Devanox\Core\Helpers\tenancy'))->toBeTrue()
        ->and(function_exists('Devanox\Core\Helpers\tenant'))->toBeTrue();
});
