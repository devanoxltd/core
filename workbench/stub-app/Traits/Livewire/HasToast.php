<?php

declare(strict_types=1);

namespace App\Traits\Livewire;

trait HasToast
{
    public function toast(
        string $message = '',
        ?string $description = null,
        string $type = 'default',
        string $position = 'bottom-left',
        ?string $html = null,
    ): void {
        $this->dispatch(
            'toast',
            message: $message,
            description: $description,
            type: $type,
            position: $position,
            html: $html,
        );
    }
}
