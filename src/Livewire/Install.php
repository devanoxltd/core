<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Helpers\EnvEditor;
use Devanox\Core\Helpers\InstallerInfo;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Install extends Component
{
    #[Locked]
    public string $activeStep = 'home';

    #[Locked]
    public ?string $nextStep = null;

    #[Locked]
    public array $steps = [];

    protected $listeners = [
        'stepReady' => 'setNextStep',
        'unsetNextStep' => 'unsetNextStep',
    ];

    public function mount()
    {
        $this->steps = [
            'home' => __('core::install.steps.home.title'),
            'requirements' => __('core::install.steps.requirements.title'),
            'permissions' => __('core::install.steps.permissions.title'),
            'database' => __('core::install.steps.database.title'),
            'migrations' => __('core::install.steps.migrations.title'),
            'admin' => __('core::install.steps.admin.title'),
            'finish' => __('core::install.steps.finish.title'),
        ];

        $this->nextStep = $this->avalableNextStep($this->activeStep);
    }

    public function setNextStep($step)
    {
        $this->nextStep = $this->avalableNextStep($step);
    }

    public function unsetNextStep()
    {
        $this->nextStep = null;
    }

    public function goToStep($step)
    {
        $this->activeStep = $step;
        $this->nextStep = null;
    }

    public function avalableNextStep($step)
    {
        $keys = array_keys($this->steps);
        $currentIndex = array_search($step, $keys);

        if ($currentIndex !== false && isset($keys[$currentIndex + 1])) {
            return $keys[$currentIndex + 1];
        }

        return null;
    }

    public function finish()
    {
        EnvEditor::setMultiple([
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ]);

        InstallerInfo::remove();
        touch(storage_path('installed'));

        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('core::livewire.install');
    }
}
