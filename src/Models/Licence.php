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

    public static function isValid(): bool
    {
        $licence = self::query()
            ->where('is_module', false)
            ->first();

        if ($licence) {
            return $licence->key && $licence->purchase_at && $licence->support_until;
        }

        return false;
    }
}
