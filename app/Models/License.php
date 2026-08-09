<?php

declare(strict_types=1);

namespace Devanox\Core\Models;

use Devanox\Core\Traits\Models\CentralConnection;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string|null $purchase_code
 * @property string $type
 * @property Carbon|null $purchase_at
 * @property Carbon|null $support_until
 * @property bool $update_notification
 * @property bool $is_module
 * @property string|null $module_name
 * @property Carbon|null $last_checked_at
 * @property string|null $signature
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> query()
 */
#[Guarded(['id'])]
final class License extends Model
{
    use CentralConnection;

    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /**
     * Check if a valid license exists for the given module (or core if null).
     */
    public static function isValidLicense(?string $module = null): bool
    {
        /** @var License|null $license */
        $license = self::query()
            ->when($module === null, fn (Builder $query) => $query->isCore())
            ->when($module !== null, fn (Builder $query) => $query->isModule((string) $module))
            ->first();

        return $license?->isValid() ?? false;
    }

    /**
     * Check if the current license instance is valid.
     */
    public function isValid(): bool
    {
        return ! empty($this->key)
            && $this->status !== 'invalid'
            && $this->purchase_at !== null
            && $this->support_until !== null;
    }

    /**
     * Check if the license has active support.
     */
    public function hasActiveSupport(): bool
    {
        return $this->support_until !== null && $this->support_until->isFuture();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'key' => 'string',
            'purchase_code' => 'string',
            'type' => 'string',
            'purchase_at' => 'datetime',
            'support_until' => 'datetime',
            'update_notification' => 'boolean',
            'is_module' => 'boolean',
            'module_name' => 'string',
            'last_checked_at' => 'datetime',
            'signature' => 'string',
            'status' => 'string',
        ];
    }

    /**
     * Scope a query to only include module licenses.
     *
     * @param  Builder<self>  $query
     */
    protected function scopeIsModule(Builder $query, string $module): void
    {
        $query->where('is_module', true)->where('module_name', $module);
    }

    /**
     * Scope a query to only include core licenses.
     *
     * @param  Builder<self>  $query
     */
    protected function scopeIsCore(Builder $query): void
    {
        $query->where('is_module', false);
    }
}
