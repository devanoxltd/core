<?php

namespace Devanox\Core\Models;

use App\Trait\Models\CentralConnection;
use Illuminate\Database\Eloquent\Model;

class Licence extends Model
{
    use CentralConnection;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'key' => 'string',
            'purchase_at' => 'datetime',
            'support_until' => 'datetime',
            'type' => 'string',
            'update_notification' => 'boolean',
            'is_module' => 'boolean',
            'module_name' => 'string',
        ];
    }

    public static function isValidLicence(?string $module = null): bool
    {
        $licence = self::query()
            ->when(!$module, function ($query) {
                return $query->where('is_module', false);
            })
            ->when($module, function ($query) use ($module) {
                return $query->where('module_name', $module);
            })
            ->first();

        if ($licence) {
            return $licence->key && $licence->purchase_at && $licence->support_until;
        }

        return false;
    }

    public function isValid(): bool
    {
        return $this->key && $this->purchase_at && $this->support_until;
    }
}
