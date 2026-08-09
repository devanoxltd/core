<?php

declare(strict_types=1);

use Devanox\Core\Models\Tenant;
use Devanox\Core\Support\Tenancy;
use Illuminate\Support\Facades\Config;

use function Devanox\Core\Helpers\tenancy;
use function Devanox\Core\Helpers\tenant;

beforeEach(function (): void {
    Tenant::query()->forceDelete();
});

it('returns tenancy instance', function (): void {
    $tenancy = tenancy();

    expect($tenancy)->toBeInstanceOf(Tenancy::class);
});

it('returns null for tenant when tenancy is disabled', function (): void {
    Config::set('tenancy.enabled', false);

    expect(tenant())->toBeNull();
});

it('returns current tenant when tenancy is enabled', function (): void {
    Config::set('tenancy.enabled', true);

    $tenant = Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => 'active',
        'config' => ['database' => ['database' => 'tenant_1', 'driver' => 'mysql']],
    ]);

    tenancy()->setTenant($tenant);

    expect(tenant())->not->toBeNull()
        ->and(tenant()->id)->toBe(1);

    tenancy()->unsetTenant();
});
