<?php

declare(strict_types=1);

use Devanox\Core\Enums\Domain\Status;
use Devanox\Core\Enums\Domain\Type;
use Devanox\Core\Models\Domain;
use Devanox\Core\Models\Tenant;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Tenant::query()->forceDelete();

    $this->tenant = Tenant::withoutEvents(fn () => Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => 'active',
    ]));
});

it('tests tenant relationship', function (): void {
    $domain = $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'type' => Type::Subdomain,
        'status' => Status::Active,
    ]);

    expect($domain->tenant->name)->toBe('Tenant 1');
});

it('tests scopes', function (): void {
    $this->tenant->domains()->create(['domain' => 'active', 'status' => Status::Active]);
    $this->tenant->domains()->create(['domain' => 'pending', 'status' => Status::Pending]);
    $this->tenant->domains()->create(['domain' => 'verified', 'status' => Status::Verified]);
    $this->tenant->domains()->create(['domain' => 'approval', 'status' => Status::Approval]);
    $this->tenant->domains()->create(['domain' => 'rejected', 'status' => Status::Rejected]);
    $this->tenant->domains()->create(['domain' => 'inactive', 'status' => Status::Inactive]);

    expect(Domain::query()->active()->count())->toBe(1)
        ->and(Domain::query()->pending()->count())->toBe(1)
        ->and(Domain::query()->verified()->count())->toBe(1)
        ->and(Domain::query()->approval()->count())->toBe(1)
        ->and(Domain::query()->rejected()->count())->toBe(1)
        ->and(Domain::query()->inactive()->count())->toBe(1);
});

it('tests is_subdomain attribute', function (): void {
    $subdomain = $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'type' => Type::Subdomain,
        'status' => Status::Active,
    ]);

    $customDomain = $this->tenant->domains()->create([
        'domain' => 'example.com',
        'type' => Type::Domain,
        'status' => Status::Active,
    ]);

    expect($subdomain->is_subdomain)->toBeTrue()
        ->and($customDomain->is_subdomain)->toBeFalse();
});

it('tests full_domain attribute', function (): void {
    Config::set('app.domain', 'myapp.com');
    Config::set('tenancy.central_domains', ['myapp.com']);

    $subdomain = $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'type' => Type::Subdomain,
        'status' => Status::Active,
    ]);

    $customDomain = $this->tenant->domains()->create([
        'domain' => 'example.com',
        'type' => Type::Domain,
        'status' => Status::Active,
    ]);

    expect($subdomain->full_domain)->toBe('tenant1.myapp.com')
        ->and($customDomain->full_domain)->toBe('example.com');
});

it('tests url attribute in local environment', function (): void {
    Config::set('app.domain', 'myapp.com');
    Config::set('tenancy.central_domains', ['myapp.com']);
    app()->detectEnvironment(fn (): string => 'local');

    $subdomain = $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'type' => Type::Subdomain,
        'status' => Status::Active,
    ]);

    expect($subdomain->url)->toBe('http://tenant1.myapp.com');
});

it('tests url attribute in production environment', function (): void {
    Config::set('app.domain', 'myapp.com');
    Config::set('tenancy.central_domains', ['myapp.com']);
    app()->detectEnvironment(fn (): string => 'production');

    $subdomain = $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'type' => Type::Subdomain,
        'status' => Status::Active,
    ]);

    expect($subdomain->url)->toBe('https://tenant1.myapp.com');

    app()->detectEnvironment(fn (): string => 'testing'); // restore
});
