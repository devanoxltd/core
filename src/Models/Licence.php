<?php

namespace Devanox\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Licence extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'key' => 'string',
            'purchase_at' => 'datetime',
            'support_until' => 'datetime',
            'type' => 'string',
            'update_notification' => 'boolean',
            'is_mobile' => 'boolean',
            'module' => 'string',
        ];
    }

    public static function isValid(): bool
    {
        $licence = self::first();

        if ($licence) {
            return $licence->key && $licence->purchase_at && $licence->support_until;
        }

        return false;
    }
}
