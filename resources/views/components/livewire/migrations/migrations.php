<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Devanox\Core\Support\StreamingOutput;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public $isMigrationRun = false;

    #[Locked]
    public $isMigrationComplete = false;

    #[Locked]
    public $isMigrationRunning = false;

    #[Locked]
    public $output = '';

    public function runAppDbMigrateInstall(): void
    {
        if ($this->isMigrationRun) {
            return;
        }

        $this->isMigrationRun = true;

        if (InstallerInfo::getStatus() === InstallerInfo::MIGRATED) {
            $this->isMigrationRunning = false;
            $this->isMigrationComplete = true;
            $this->dispatch('stepReady', step: 'migrations')->to('core::install');

            return;
        }

        $this->isMigrationRunning = true;
        $this->isMigrationComplete = false;

        if (InstallerInfo::getStatus() === InstallerInfo::DB_CONFIGURED) {
            // Run the installation command and stream output
            $this->streamInstallation();
        }
    }

    public function checkStatus(): void
    {
        if ($this->isMigrationComplete) {
            $this->dispatch('stepReady', step: 'migrations')->to('core::install');

            return;
        }

        if (InstallerInfo::getStatus() === InstallerInfo::MIGRATING) {
            $this->isMigrationRunning = true;
            $this->isMigrationComplete = false;
        } elseif (InstallerInfo::getStatus() === InstallerInfo::MIGRATED) {
            $this->isMigrationRunning = false;
            $this->isMigrationComplete = true;
            $this->dispatch('stepReady', step: 'migrations')->to('core::install');
        }
    }

    protected function streamInstallation(): void
    {
        try {
            if (! array_key_exists('app:install', Artisan::all())) {
                throw new Exception(__('core::install.error.app_install_missing'));
            }

            // Create a streaming output that sends each line immediately
            $output = new StreamingOutput(function (string $line): void {
                $this->stream(
                    content: $line,
                    to: 'output',
                );
                $this->output .= $line;
            });

            // Run the command and stream output in real-time
            $exitCode = Artisan::call('app:install', [], $output);

            if ($exitCode !== 0) {
                throw new Exception(__('core::install.error.migration_failed', ['code' => $exitCode]));
            }
        } catch (Exception $exception) {
            $errorMessage = "\n\nError: " . $exception->getMessage() . "\n";
            $this->stream(
                content: $errorMessage,
                to: 'output',
            );
            $this->output .= $errorMessage;

            $this->isMigrationRunning = false;
            InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURED);
        }
    }
};
