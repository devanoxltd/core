<?php

namespace Devanox\Core\Commands\Module;

use Devanox\Core\Support\Module;
use Illuminate\Console\Command;

class AllList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all modules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modules = Module::get();

        $this->info('Modules:');
        $this->table(['Module', 'Status'], array_map(function (object $module) {
            return [$module->name, $module->enabled ? 'Enabled' : 'Disabled'];
        }, $modules->toArray()));

        return Command::SUCCESS;
    }
}
