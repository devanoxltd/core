<?php

declare(strict_types=1);

namespace Devanox\Core\Contracts\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contract for tenant models used by the tenancy system.
 *
 * @method mixed getKey() Get the primary key value.
 *
 * @property int|string $id
 * @property string $name
 * @property array<string, mixed>|null $config
 * @property-read Collection<int, Model&Domain> $domains
 *
 * @phpstan-require-extends Model
 */
interface Tenant
{
    /**
     * Get all domains for the tenant.
     *
     * @return HasMany<Model&Domain, covariant Model&Tenant>
     */
    public function domains(): HasMany;

    /**
     * Get the approved (active) domains for the tenant.
     *
     * @return HasMany<Model&Domain, covariant Model&Tenant>
     */
    public function approvedDomains(): HasMany;

    /**
     * The user that owns this tenant.
     *
     * @return BelongsTo<Model, covariant Model&Tenant>
     */
    public function user(): BelongsTo;
}
