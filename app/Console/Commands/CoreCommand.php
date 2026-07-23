<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Illuminate\Console\Command;

class CoreCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'core:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package core.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Core placeholder command executed.');

        return self::SUCCESS;
    }
}
