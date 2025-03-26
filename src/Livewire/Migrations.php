<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Helpers\InstallerInfo;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class Migrations extends Component
{
    public $isMigrationRun = false;

    public $isMigrationComplete = false;

    public $isMigrationRunning = false;

    public $isMigrationError = false;

    public $isMigrationSuccess = false;

    public function runAppDbMigrateInstall()
    {
        if ($this->isMigrationRun) {
            return;
        }

        $this->isMigrationRun = true;
        $this->isMigrationRunning = true;
        $this->isMigrationComplete = false;

        if (InstallerInfo::getStatus() == 'not_started') {
            defer(
                function () {
                    Artisan::call('app:install');
                }
            );
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
}
