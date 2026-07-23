<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Placeholder Artisan command shipped by the package core.')]
#[Signature('core:placeholder')]
final class CoreCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Core placeholder command executed.');

        return self::SUCCESS;
    }
}
