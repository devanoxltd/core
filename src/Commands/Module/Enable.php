<?php

namespace Devanox\Core\Commands\Module;

use Devanox\Core\Support\Module;
use Illuminate\Console\Command;

class Enable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:enable {module?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable a module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = $this->argument('module');

        if (! $module) {
            $modules = Module::all();

            foreach ($modules as $module) {
                if (Module::isEnabled($module)) {
                    $this->warn("Module {$module} is already enabled!");

                    continue;
                }

                Module::enable($module);
                $this->info("Module {$module} enabled successfully!");
            }

            $this->newLine();
            $this->line('---------------------------------');
            $this->info('All module enabled successfully!');

            return Command::SUCCESS;
        }

        if (! Module::exist($module)) {
            $this->error('Module does not exist!');

            return Command::FAILURE;
        }

        if (Module::isEnabled($module)) {
            $this->warn("Module {$module} is already enabled!");

            return Command::SUCCESS;
        }

        Module::enable($module);
        $this->info("Module {$module} enabled successfully!");

        return Command::SUCCESS;
    }
}
