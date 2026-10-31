<?php

declare(strict_types=1);

namespace Devanox\Core\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class Secret
{
    public const string PREFIX = 'enc:';

    public const string MASK = '********';

    /**
     * Encrypt a secret for storage; empty and already encrypted values are returned untouched.
     */
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '' || str_starts_with($value, self::PREFIX)) {
            return $value;
        }

        return self::PREFIX . Crypt::encryptString($value);
    }

    /**
     * Decrypt a stored secret; legacy plain values pass through and undecryptable values return null.
     */
    public static function decrypt(?string $value): ?string
    {
        if ($value === null || ! str_starts_with($value, self::PREFIX)) {
            return $value;
        }

        try {
            return Crypt::decryptString(mb_substr($value, mb_strlen(self::PREFIX)));
        } catch (DecryptException) {
            return null;
        }
    }
}
