<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

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
            $this->startInstallation();
        }
    }

    public function checkStatus(): void
    {
        $this->readOutput();

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
        } elseif (InstallerInfo::getStatus() === InstallerInfo::DB_CONFIGURED) {
            // The installation command failed and reset the status.
            $this->isMigrationRunning = false;
        }
    }

    protected function startInstallation(): void
    {
        try {
            $logFile = storage_path('install-output.log');
            file_put_contents($logFile, '');

            InstallerInfo::setStatus(InstallerInfo::MIGRATING);

            $php = (new PhpExecutableFinder)->find() ?: 'php';

            // Run detached so web server timeouts or dropped requests cannot interrupt the installation.
            Process::fromShellCommandline(
                sprintf(
                    '%s %s app:install --no-interaction --no-ansi > %s 2>&1 < /dev/null &',
                    escapeshellarg($php),
                    escapeshellarg(base_path('artisan')),
                    escapeshellarg($logFile),
                ),
                base_path(),
            )->run();
        } catch (Exception $exception) {
            $this->output = "\n\nError: " . $exception->getMessage() . "\n";
            $this->isMigrationRunning = false;
            InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURED);
        }
    }

    protected function readOutput(): void
    {
        $logFile = storage_path('install-output.log');

        if (is_file($logFile)) {
            $this->output = (string) file_get_contents($logFile);
        }
    }
};
