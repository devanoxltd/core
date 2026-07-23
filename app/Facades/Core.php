<?php

declare(strict_types=1);

namespace Devanox\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Devanox\Core\Core
 */
final class Core extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Devanox\Core\Core::class;
    }
}
