<?php

namespace Devanox\Core\Commands\Module;

use Devanox\Core\Support\Module;
use Illuminate\Console\Command;

class Disable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:disable {module?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable a module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = $this->argument('module');

        if (! $module) {
            Module::get()->each(function ($module) {
                if ($module->enabled) {
                    Module::disable($module->name);
                    $this->info("Module {$module->name} disabled successfully!");
                } else {
                    $this->warn("Module {$module->name} is already disabled!");
                }
            });

            $this->newLine();
            $this->line('---------------------------------');
            $this->info('All module disabled successfully!');

            return Command::SUCCESS;
        }

        if (! Module::exist($module)) {
            $this->error('Module does not exist!');

            return Command::FAILURE;
        }

        if ((Module::isDisabled($module))) {
            $this->warn("Module {$module} is already disabled!");

            return Command::SUCCESS;
        }

        Module::disable($module);

        $this->info("Module {$module} disabled successfully!");

        return Command::SUCCESS;
    }
}
