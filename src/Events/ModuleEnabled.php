<?php

namespace Devanox\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleEnabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $module) {}
}
