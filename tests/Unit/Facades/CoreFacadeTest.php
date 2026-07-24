<?php

declare(strict_types=1);

use Devanox\Core\Core;
use Devanox\Core\Facades\Core as CoreFacade;

it('resolves the core instance via facade', function (): void {
    expect(CoreFacade::getFacadeRoot())->toBeInstanceOf(Core::class);
});

it('returns the same instance via facade', function (): void {
    expect(CoreFacade::getFacadeRoot())->toBe(CoreFacade::getFacadeRoot());
});
