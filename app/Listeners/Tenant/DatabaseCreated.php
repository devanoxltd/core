<?php

declare(strict_types=1);

namespace Devanox\Core\Listeners\Tenant;

use Devanox\Core\Events\Tenant\DatabaseCreated as TenantDatabaseCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

#[Queue('tenant')]
final class DatabaseCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TenantDatabaseCreatedEvent $event): void
    {
        Artisan::call('tenant', [
            'artisanCommand' => 'migrate --path=database/migrations/tenant --force',
            '--tenant' => $event->tenant->id,
        ]);

        Artisan::call('tenant', [
            'artisanCommand' => 'module:migrate',
            '--tenant' => $event->tenant->id,
        ]);
    }
}
