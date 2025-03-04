<?php

namespace Devanox\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleDisabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $module) {}
}
