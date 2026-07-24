<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands\Module;

use Devanox\Core\Models\License;
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

#[AsCommand(name: 'module:enable', description: 'Enable a module')]
#[Description('Enable a module')]
#[Signature('module:enable {module?} {--all : Enable all modules} {--force : Force the operation to run when in production}')]
final class Enable extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string|null $module */
        $module = $this->argument('module');
        $enableAll = (bool) $this->option('all');
        $force = (bool) $this->option('force');

        if (! $module && ! $enableAll) {
            $module = $this->promptForModule();

            if (! $module) {
                return Command::SUCCESS;
            }
        }

        if ($enableAll) {
            return $this->enableAllModules($force);
        }

        return $this->enableSingleModule((string) $module);
    }

    /**
     * Prompt the user to select a module to enable.
     */
    private function promptForModule(): ?string
    {
        /** @var array<int|string, string> $disabledModules */
        $disabledModules = Module::get()
            ->reject(fn (object $m): bool => (bool) $m->enabled)
            ->pluck('name')
            ->toArray();

        if (empty($disabledModules)) {
            warning('There are no disabled modules to enable.');

            return null;
        }

        return (string) select(
            label: 'Which module do you want to enable?',
            options: $disabledModules,
        );
    }

    /**
     * Enable all valid modules.
     */
    private function enableAllModules(bool $force): int
    {
        if (! $force) {
            $confirmed = confirm('Are you sure you want to enable all modules?');

            if (! $confirmed) {
                info('Action canceled.');

                return Command::SUCCESS;
            }
        }

        $licenses = License::query()->where('is_module', true)->get();

        $hasErrors = false;

        Module::get()->each(function (object $m) use ($licenses, &$hasErrors): void {
            if ($m->enabled) {
                warning(sprintf('Module %s is already enabled!', $m->name));
            } else {
                /** @var License|null $license */
                $license = $licenses->firstWhere('module_name', $m->name);

                if (! $license || ! $license->isValid()) {
                    error(sprintf('Module %s does not have a valid license!', $m->name));
                    $hasErrors = true;
                } else {
                    try {
                        Module::enable($m->name);
                        info(sprintf('Module %s enabled successfully!', $m->name));
                    } catch (Exception $e) {
                        error(sprintf('Failed to enable module %s: %s', $m->name, $e->getMessage()));
                        $hasErrors = true;
                    }
                }
            }
        });

        if ($hasErrors) {
            warning('Finished with some errors.');

            return Command::FAILURE;
        }

        info('All valid modules processed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Enable a single module.
     */
    private function enableSingleModule(string $module): int
    {
        $module = Str::studly($module);

        if (! Module::exist($module)) {
            error(sprintf('Module %s does not exist!', $module));

            return Command::FAILURE;
        }

        if (Module::isEnabled($module)) {
            warning(sprintf('Module %s is already enabled!', $module));

            return Command::SUCCESS;
        }

        if (! License::isValidLicense($module)) {
            error(sprintf('Module %s does not have a valid license!', $module));

            return Command::FAILURE;
        }

        try {
            Module::enable($module);
            info(sprintf('Module %s enabled successfully!', $module));
        } catch (Exception $exception) {
            error(sprintf('Failed to enable module %s: %s', $module, $exception->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
