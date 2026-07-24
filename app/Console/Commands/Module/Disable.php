<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands\Module;

use Devanox\Core\Support\Module;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'module:disable', description: 'Disable a module')]
#[Description('Disable a module')]
#[Signature('module:disable {module?} {--all : Disable all modules} {--force : Force the operation to run when in production}')]
final class Disable extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string|null $module */
        $module = $this->argument('module');
        $disableAll = (bool) $this->option('all');
        $force = (bool) $this->option('force');

        if (! $module && ! $disableAll) {
            $module = $this->promptForModule();

            if (! $module) {
                return Command::SUCCESS;
            }
        }

        if ($disableAll) {
            return $this->disableAllModules($force);
        }

        return $this->disableSingleModule((string) $module);
    }

    /**
     * Prompt the user to select a module to disable.
     */
    private function promptForModule(): ?string
    {
        /** @var array<int|string, string> $enabledModules */
        $enabledModules = Module::get()
            ->filter(fn (object $m) => $m->enabled)
            ->pluck('name')
            ->toArray();

        if (empty($enabledModules)) {
            warning('There are no enabled modules to disable.');

            return null;
        }

        return (string) select(
            label: 'Which module do you want to disable?',
            options: $enabledModules,
        );
    }

    /**
     * Disable all active modules.
     */
    private function disableAllModules(bool $force): int
    {
        if (! $force) {
            $confirmed = confirm('Are you sure you want to disable all modules?');

            if (! $confirmed) {
                info('Action canceled.');

                return Command::SUCCESS;
            }
        }

        $hasErrors = false;

        Module::get()->each(function (object $m) use (&$hasErrors): void {
            if ($m->enabled) {
                try {
                    Module::disable($m->name);
                    info(sprintf('Module %s disabled successfully!', $m->name));
                } catch (Exception $e) {
                    error(sprintf('Failed to disable module %s: %s', $m->name, $e->getMessage()));
                    $hasErrors = true;
                }
            } else {
                warning(sprintf('Module %s is already disabled!', $m->name));
            }
        });

        if ($hasErrors) {
            warning('Finished with some errors.');

            return Command::FAILURE;
        }

        info('All valid modules disabled successfully!');

        return Command::SUCCESS;
    }

    /**
     * Disable a single module.
     */
    private function disableSingleModule(string $module): int
    {
        $module = Str::studly($module);

        if (! Module::exist($module)) {
            error(sprintf('Module %s does not exist!', $module));

            return Command::FAILURE;
        }

        if (Module::isDisabled($module)) {
            warning(sprintf('Module %s is already disabled!', $module));

            return Command::SUCCESS;
        }

        try {
            Module::disable($module);
            info(sprintf('Module %s disabled successfully!', $module));
        } catch (Exception $exception) {
            error(sprintf('Failed to disable module %s: %s', $module, $exception->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
