<?php

declare(strict_types=1);

use Devanox\Core\Enums\Domain\Status as DomainStatus;
use Devanox\Core\Enums\Tenant\Status;
use Devanox\Core\Models\Tenant;
use Illuminate\Support\Collection;
use Workbench\App\Models\User;

beforeEach(function (): void {
    Tenant::query()->forceDelete();

    $this->tenant = Tenant::query()->forceCreate([
        'id' => 1,
        'user_id' => 1,
        'name' => 'Tenant 1',
        'email' => 'test@example.com',
        'status' => Status::Active,
        'config' => ['foo' => 'bar'],
    ]);

    $this->suspendedTenant = Tenant::query()->forceCreate([
        'id' => 2,
        'user_id' => 1,
        'name' => 'Tenant 2',
        'email' => 'test@example.com',
        'status' => Status::Suspended,
    ]);
});

it('tests domains relationship', function (): void {
    $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'status' => DomainStatus::Active,
    ]);

    $this->tenant->domains()->create([
        'domain' => 'tenant1-pending',
        'status' => DomainStatus::Pending,
    ]);

    expect($this->tenant->domains()->count())->toBe(2);
});

it('tests approvedDomains relationship', function (): void {
    $this->tenant->domains()->create([
        'domain' => 'tenant1',
        'status' => DomainStatus::Active,
    ]);

    $this->tenant->domains()->create([
        'domain' => 'tenant1-pending',
        'status' => DomainStatus::Pending,
    ]);

    expect($this->tenant->approvedDomains()->count())->toBe(1)
        ->and($this->tenant->approvedDomains->first()->domain)->toBe('tenant1');
});

it('tests user relationship', function (): void {
    $user = User::query()->create([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'secret',
    ]);

    $this->tenant->user_id = $user->id;
    $this->tenant->save();

    expect($this->tenant->user->name)->toBe('Test');
});

it('tests active scope', function (): void {
    $activeTenants = Tenant::query()->active()->get();

    expect($activeTenants->count())->toBe(1)
        ->and($activeTenants->first()->id)->toBe(1);
});

it('tests suspended scope', function (): void {
    $suspendedTenants = Tenant::query()->suspended()->get();

    expect($suspendedTenants->count())->toBe(1)
        ->and($suspendedTenants->first()->id)->toBe(2);
});

it('tests casting', function (): void {
    $tenant = Tenant::query()->find(1);
    expect($tenant->config)->toBeInstanceOf(Collection::class)
        ->and($tenant->config['foo'])->toBe('bar')
        ->and($tenant->status)->toBeInstanceOf(Status::class);
});
