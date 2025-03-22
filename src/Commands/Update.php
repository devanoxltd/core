<?php

namespace Devanox\Core\Commands;

use Illuminate\Console\Command;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('migrate');
        $this->info('Application updated.');

        return Command::SUCCESS;
    }
}
