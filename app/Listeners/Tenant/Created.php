<?php

declare(strict_types=1);

namespace Devanox\Core\Listeners\Tenant;

use Devanox\Core\Events\Tenant\Created as TenantCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

#[Queue('tenant')]
final class Created implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TenantCreatedEvent $event): void
    {
        Artisan::call('tenant:create-database', ['id' => $event->tenant->getKey()]);
    }
}
