<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Helpers\InstallerInfo;
use Devanox\Core\Support\StreamingOutput;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Migrations extends Component
{
    #[Locked]
    public $isMigrationRun = false;

    #[Locked]
    public $isMigrationComplete = false;

    #[Locked]
    public $isMigrationRunning = false;

    #[Locked]
    public $output = '';

    public function runAppDbMigrateInstall()
    {
        if ($this->isMigrationRun) {
            return;
        }

        $this->isMigrationRun = true;
        $this->isMigrationRunning = true;
        $this->isMigrationComplete = false;

        if (InstallerInfo::getStatus() == 'not_started') {
            // Run the installation command and stream output
            $this->streamInstallation();
        }
    }

    public function checkStatus()
    {
        if ($this->isMigrationComplete) {
            $this->dispatch('stepReady', step: 'migrations')->to(Install::class);

            return;
        }

        if (InstallerInfo::getStatus() == 'migrating') {
            $this->isMigrationRunning = true;
            $this->isMigrationComplete = false;
        } elseif (InstallerInfo::getStatus() == 'migrated') {
            $this->isMigrationRunning = false;
            $this->isMigrationComplete = true;
            $this->dispatch('stepReady', step: 'migrations')->to(Install::class);
        }
    }

    public function render()
    {
        return view('core::livewire.migrations');
    }

    protected function streamInstallation()
    {
        try {
            // Create a streaming output that sends each line immediately
            $output = new StreamingOutput(function ($line) {
                $this->stream(
                    to: 'output',
                    content: $line,
                );
                $this->output .= $line;
            });

            // Run the command and stream output in real-time
            Artisan::call('app:install', [], $output);
        } catch (Exception $e) {
            $errorMessage = "\n\nError: " . $e->getMessage() . "\n";
            $this->stream(
                to: 'output',
                content: $errorMessage,
            );
            $this->output .= $errorMessage;
        }
    }
}
