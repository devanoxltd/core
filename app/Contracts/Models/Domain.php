<?php

declare(strict_types=1);

namespace Devanox\Core\Contracts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contract for domain models used by the tenancy system.
 *
 * @method mixed getKey() Get the primary key value.
 *
 * @property string $domain
 * @property string $url
 *
 * @phpstan-require-extends Model
 */
interface Domain
{
    /**
     * A domain belongs to a single tenant.
     *
     * @return BelongsTo<Model&Tenant, covariant Model&Domain>
     */
    public function tenant(): BelongsTo;
}
