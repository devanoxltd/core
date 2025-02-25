<?php

namespace Devanox\Core\Providers;

use Devanox\Core\Support\Module;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register your package's services here.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->checkModulesCapabilities();
        $this->registerCommands();
    }

    private function checkModulesCapabilities(): void
    {
        $modules = Module::getModules();
        $moduleIds = [];

        foreach ($modules as $module) {
            $prefix = Module::prefix($module);
            $id = config("{$prefix}.id", null);

            if ($id !== null) {
                $moduleIds[$id] = $module;
            }
        }

        // Check requirements
        foreach ($modules as $module) {
            $prefix = Module::prefix($module);
            $requiredModules = config("{$prefix}.requiredModules", []);

            if (empty($requiredModules)) {
                continue;
            }

            foreach ($requiredModules as $requiredModule) {
                if (! in_array($requiredModule, array_keys($moduleIds))) {
                    Module::disable($module);
                    $message = "Module {$module} requires a module that is not installed. Module {$module} is disabled. Required module ID: {$requiredModule}";
                    Log::notice($message);
                    throw new Exception($message);
                }
            }
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Devanox\Core\Commands\Module\AllList::class,
                \Devanox\Core\Commands\Module\Disable::class,
                \Devanox\Core\Commands\Module\Enable::class,
                \Devanox\Core\Commands\CleanUp::class,
            ]);
        }
    }
}
