<?php

declare(strict_types=1);

namespace Devanox\Core\Events\Tenant;

use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class Updating
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public TenantContract $tenant) {}
}
