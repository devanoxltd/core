<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands\Module;

use Devanox\Core\Support\Module;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'module:list', description: 'List all modules')]
#[Description('List all modules')]
#[Signature('module:list {--enabled : List only enabled modules} {--disabled : List only disabled modules}')]
final class AllList extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modules = Module::get();

        if ($this->option('enabled')) {
            $modules = $modules->filter(fn (object $m) => $m->enabled);
        } elseif ($this->option('disabled')) {
            $modules = $modules->reject(fn (object $m): bool => (bool) $m->enabled);
        }

        if ($modules->isEmpty()) {
            warning('No modules found.');

            return Command::SUCCESS;
        }

        table(
            ['Module', 'ID', 'Version', 'Status'],
            $modules->sortBy('name')->map(fn (object $module): array => [
                (string) $module->name,
                (string) ($module->id ?? '-'),
                isset(((array) $module->config)['version']) && (is_string(((array) $module->config)['version']) || is_numeric(((array) $module->config)['version'])) ? (string) ((array) $module->config)['version'] : '-',
                $module->enabled ? 'Enabled' : 'Disabled',
            ])->all(),
        );

        return Command::SUCCESS;
    }
}
