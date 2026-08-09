<?php

declare(strict_types=1);

// @php-cs-fixer-ignore final_class

namespace Devanox\Core\Models;

use Carbon\CarbonImmutable;
use Devanox\Core\Contracts\Models\Domain as DomainContract;
use Devanox\Core\Enums\Domain\Status;
use Devanox\Core\Enums\Domain\Type;
use Devanox\Core\Events\Domain as Events;
use Devanox\Core\Traits\Models\CentralConnection;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function Devanox\Core\Helpers\tenancy;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $domain
 * @property Type $type
 * @property Status $status
 * @property CarbonImmutable|null $verified_at
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $rejected_at
 * @property string|null $rejection_reason
 * @property string|null $verification_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read bool $is_subdomain
 * @property-read string $full_domain
 * @property-read string $url
 */
#[Guarded(['id'])]
class Domain extends Model implements DomainContract
{
    use CentralConnection;

    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'saving' => Events\Saving::class,
        'saved' => Events\Saved::class,
        'creating' => Events\Creating::class,
        'created' => Events\Created::class,
        'updating' => Events\Updating::class,
        'updated' => Events\Updated::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
    ];

    /**
     * A domain belongs to a single tenant.
     *
     * @return BelongsTo<\Devanox\Core\Contracts\Models\Tenant&Model, $this>
     */
    public function tenant(): BelongsTo
    {
        // Resolve the tenant model from your package config
        /** @var class-string<\Devanox\Core\Contracts\Models\Tenant&Model> $tenantClass */
        $tenantClass = config('tenancy.models.tenant', Tenant::class);

        /** @var BelongsTo<\Devanox\Core\Contracts\Models\Tenant&Model, $this> $relation */
        $relation = $this->belongsTo($tenantClass);

        return $relation;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', Status::Pending);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function verified(Builder $query): Builder
    {
        return $query->where('status', Status::Verified);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function approval(Builder $query): Builder
    {
        return $query->where('status', Status::Approval);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function rejected(Builder $query): Builder
    {
        return $query->where('status', Status::Rejected);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function inactive(Builder $query): Builder
    {
        return $query->where('status', Status::Inactive);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => Type::class,
            'status' => Status::class,
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'rejection_reason' => 'string',
            'verification_token' => 'string',
        ];
    }

    /**
     * Determine if the domain is a subdomain.
     *
     * @return Attribute<bool, never>
     */
    protected function isSubdomain(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->type->is(Type::Subdomain),
        );
    }

    /**
     * Get the full domain.
     *
     * @return Attribute<string, never>
     */
    protected function fullDomain(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->is_subdomain ? $this->domain . '.' . tenancy()->appDomain() : $this->domain,
        );
    }

    /**
     * Get the URL for the domain.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (app()->isProduction() ? 'https://' : 'http://') . $this->full_domain,
        );
    }
}
