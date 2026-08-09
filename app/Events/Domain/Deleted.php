<?php

declare(strict_types=1);

namespace Devanox\Core\Events\Domain;

use Devanox\Core\Contracts\Models\Domain as DomainContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class Deleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public DomainContract $domain) {}
}
