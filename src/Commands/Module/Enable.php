<?php

namespace Devanox\Core\Commands\Module;

use Devanox\Core\Models\License;
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
            $licenses = License::where('is_module', true)
                ->get();
            Module::get()->each(function ($module) use ($licenses) {
                if ($module->enabled) {
                    $this->warn("Module {$module->name} is already enabled!");
                } else {
                    $license = $licenses->firstWhere('module_name', $module->name);
                    if (! $license) {
                        $this->error("Module {$module->name} is not licensed!");
                    } else {
                        Module::enable($module->name);
                        $this->info("Module {$module->name} enabled successfully!");
                    }
                }
            });

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

        $license = License::where('is_module', true)
            ->where('module_name', $module)
            ->first();

        if (! $license) {
            $this->error("Module {$module} is not licensed!");

            return Command::FAILURE;
        }

        Module::enable($module);
        $this->info("Module {$module} enabled successfully!");

        return Command::SUCCESS;
    }
}
