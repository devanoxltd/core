<?php

declare(strict_types=1);

use Devanox\Core\Events\ModuleDisabled;
use Devanox\Core\Events\ModuleEnabled;
use Illuminate\Support\Facades\Event;

it('dispatches module enabled event', function (): void {
    Event::fake([ModuleEnabled::class]);

    event(new ModuleEnabled('TestModule'));

    Event::assertDispatched(ModuleEnabled::class, fn (ModuleEnabled $event): bool => $event->module === 'TestModule');
});

it('dispatches module disabled event', function (): void {
    Event::fake([ModuleDisabled::class]);

    event(new ModuleDisabled('TestModule'));

    Event::assertDispatched(ModuleDisabled::class, fn (ModuleDisabled $event): bool => $event->module === 'TestModule');
});
