<?php

declare(strict_types=1);

namespace Devanox\Core\Helpers;

use Illuminate\Support\Arr;

final class Configuration
{
    /**
     * @param  array<string, mixed>  $configurations
     */
    public static function apply(array $configurations): void
    {
        /** @var array<string, mixed> $dottedConfig */
        $dottedConfig = Arr::dot($configurations);
        config($dottedConfig);
    }
}
