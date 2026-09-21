<?php

declare(strict_types=1);

use Devanox\Core\Models\License;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public ?License $license = null;

    public ?string $dbVersion = null;

    public function mount(): void
    {
        $this->license = License::query()->isCore()->first();
        $connection = DB::connection();
        $this->dbVersion = match ($connection->getDriverName()) {
            'sqlite' => $connection->selectOne('SELECT sqlite_version() as version')->version ?? null,
            default => $connection->selectOne('SELECT version() as version')->version ?? null,
        };
    }
};
