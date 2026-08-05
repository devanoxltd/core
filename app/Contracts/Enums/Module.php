<?php

declare(strict_types=1);

namespace Devanox\Core\Contracts\Enums;

/**
 * TODO: add doc the use of this interface in the app
 */
interface Module
{
    public function label(): string;

    public function permissions(): array;

    public function parent(): ?self;
}
