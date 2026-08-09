<?php

declare(strict_types=1);

// @php-cs-fixer-ignore final_class

namespace Devanox\Core\Models;

use Carbon\CarbonImmutable;
use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Enums\Domain\Status as DomainStatus;
use Devanox\Core\Enums\Tenant\Status;
use Devanox\Core\Events\Tenant as Events;
use Devanox\Core\Traits\Models\CentralConnection;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address
 * @property string|null $description
 * @property Status $status
 * @property array<string, mixed>|null $config
 * @property bool $is_self_hosted
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, \Devanox\Core\Contracts\Models\Domain&Model> $domains
 */
#[Guarded(['id'])]
class Tenant extends Model implements TenantContract
{
    use CentralConnection;

    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /**
     * The event map for the model.
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
     * Get all domains for the tenant.
     *
     * @return HasMany<\Devanox\Core\Contracts\Models\Domain&Model, $this>
     */
    public function domains(): HasMany
    {
        /** @var class-string<\Devanox\Core\Contracts\Models\Domain&Model> $domainClass */
        $domainClass = config('tenancy.models.domain', Domain::class);

        /** @var HasMany<\Devanox\Core\Contracts\Models\Domain&Model, $this> $relation */
        $relation = $this->hasMany($domainClass);

        return $relation;
    }

    /**
     * Get the approved (active) domains for the tenant.
     *
     * @return HasMany<\Devanox\Core\Contracts\Models\Domain&Model, $this>
     */
    public function approvedDomains(): HasMany
    {
        /** @var HasMany<\Devanox\Core\Contracts\Models\Domain&Model, $this> $relation */
        $relation = $this->domains()->where('status', DomainStatus::Active); // @phpstan-ignore method.private

        return $relation;
    }

    /**
     * The user that owns this tenant.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<Model> $userClass */
        $userClass = config('auth.providers.users.model', 'App\Models\User');

        /** @var BelongsTo<Model, $this> $relation */
        $relation = $this->belongsTo($userClass);

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
    protected function suspended(Builder $query): Builder
    {
        return $query->where('status', Status::Suspended);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'name' => 'string',
            'email' => 'string',
            'phone' => 'string',
            'website' => 'string',
            'address' => 'string',
            'description' => 'string',
            'config' => AsCollection::class,
        ];
    }
}
