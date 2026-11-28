<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Devanox\Core\Contracts\Models\Tenant as TenantContract;
use Devanox\Core\Enums\Tenant\Status;
use Devanox\Core\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Process\InvokedProcess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Throwable;

#[Signature('tenant:queue-manage
    {--queue= : Comma-separated queue names}
    {--workers=4 : Maximum simultaneous tenant workers}
    {--interval=5 : Seconds between discovery passes}
    {--timeout=60 : Default job timeout in seconds (jobs may override it)}
    {--tries=3 : Maximum job attempts; 0 means unlimited}
    {--backoff=5 : Seconds before retrying a failed job}
    {--memory=128 : Worker memory limit in megabytes}
    {--force : Run during maintenance mode}
    {--once : Process one discovery pass and exit}')]
#[Description('Discover tenants and process their database queues with a bounded worker pool')]
final class TenantQueueManageCommand extends Command
{
    private bool $stopping = false;

    private int $queueOffset = 0;

    public function handle(): int
    {
        if (config('tenancy.enabled', false) !== true) {
            $this->error(__('core::tenancy.commands.queue.tenancy_required'));

            return self::FAILURE;
        }

        // @codeCoverageIgnoreStart
        if (! extension_loaded('pcntl')) {
            $this->error(__('core::tenancy.commands.queue.pcntl_required'));

            return self::FAILURE;
        }

        // @codeCoverageIgnoreEnd

        foreach (['workers', 'interval', 'timeout', 'memory'] as $option) {
            $value = $this->option($option);

            if (! is_string($value) || ! ctype_digit($value) || (int) $value < 1) {
                $this->error(__('core::tenancy.commands.queue.positive_option', ['option' => $option]));

                return self::FAILURE;
            }
        }

        foreach (['tries', 'backoff'] as $option) {
            $value = $this->option($option);

            if (! is_string($value) || ! ctype_digit($value)) {
                $this->error(__('core::tenancy.commands.queue.non_negative_option', ['option' => $option]));

                return self::FAILURE;
            }
        }

        $tenantClass = config('tenancy.models.tenant', Tenant::class);

        if (! is_string($tenantClass) || ! is_subclass_of($tenantClass, Model::class) || ! is_subclass_of($tenantClass, TenantContract::class)) {
            $this->error(__('core::tenancy.commands.queue.invalid_model'));

            return self::FAILURE;
        }

        $this->stopping = false;
        $this->trap([SIGTERM, SIGINT], function (): void {
            $this->stopping = true;
        });

        do {
            $status = $this->processTenants($tenantClass);

            if ($this->option('once')) {
                return $status;
            }

            if (! $this->stopping) {
                Sleep::sleep((int) $this->option('interval'));
            }
        } while (! $this->stopping);

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model&TenantContract>  $tenantClass
     */
    private function processTenants(string $tenantClass): int
    {
        $workers = [];
        $failed = false;
        $limit = (int) $this->option('workers');
        $keyName = (new $tenantClass)->getKeyName();

        try {
            foreach ($tenantClass::query()->where('status', Status::Active->value)->lazyById(100, $keyName) as $tenant) {
                if ($this->stopping) {
                    break;
                }

                while (count($workers) >= $limit) {
                    $this->collectWorkers($workers, $failed);

                    if (count($workers) >= $limit) {
                        Sleep::usleep(100_000);
                    }
                }

                if ($this->stopping) {
                    break;
                }

                $key = $tenant->getKey();

                if ((! is_string($key) && ! is_int($key)) || (string) $key === '') {
                    $this->error(__('core::tenancy.commands.queue.invalid_tenant_identifier'));
                    $failed = true;

                    continue;
                }

                try {
                    $workers[] = [
                        'tenant' => (string) $key,
                        'process' => Process::path(base_path())
                            ->forever()
                            ->quietly()
                            ->start($this->workerCommand((string) $key), function (string $type, string $output) use ($key): void {
                                $output = mb_trim($output);

                                if ($output === '') {
                                    return;
                                }

                                if ($type === 'err') {
                                    $this->error(__('core::tenancy.commands.queue.worker_output', ['tenant' => $key, 'output' => $output]));
                                } else {
                                    $this->line(__('core::tenancy.commands.queue.worker_output', ['tenant' => $key, 'output' => $output]));
                                }
                            }),
                    ];
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error(__('core::tenancy.commands.queue.worker_start_failed', ['tenant' => $key]));
                    $failed = true;
                }
            }
        } finally {
            while ($workers !== []) {
                $this->collectWorkers($workers, $failed);

                if ($workers !== []) {
                    Sleep::usleep(100_000);
                }
            }
        }

        $this->queueOffset++;

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int, array{tenant: string, process: InvokedProcess}>  $workers
     */
    private function collectWorkers(array &$workers, bool &$failed): void
    {
        foreach ($workers as $index => $worker) {
            $process = $worker['process'];

            if ($process->running()) {
                continue;
            }

            $result = $process->wait();

            if (! $result->successful()) {
                $this->error(__('core::tenancy.commands.queue.worker_exited', ['tenant' => $worker['tenant'], 'code' => $result->exitCode()]));
                $failed = true;
            }

            unset($workers[$index]);
        }
    }

    /**
     * @return list<string>
     */
    private function workerCommand(string $tenantId): array
    {
        $command = [PHP_BINARY, base_path('artisan'), 'tenant:queue-work', $tenantId, '--stop-when-empty', '--max-jobs=1', '--sleep=0', '--no-interaction'];

        $queue = $this->option('queue');

        if (is_string($queue) && $queue !== '') {
            $queues = array_values(array_filter(
                array_map(mb_trim(...), explode(',', $queue)),
                fn (string $name): bool => $name !== '',
            ));

            if ($queues !== []) {
                $offset = $this->queueOffset % count($queues);
                $command[] = '--queue=' . implode(',', [...array_slice($queues, $offset), ...array_slice($queues, 0, $offset)]);
            }
        }

        foreach (['timeout', 'tries', 'backoff', 'memory', 'env'] as $option) {
            $value = $this->option($option);

            if (is_string($value) && $value !== '') {
                $command[] = "--{$option}={$value}";
            }
        }

        if ($this->option('force')) {
            $command[] = '--force';
        }

        return $command;
    }
}
