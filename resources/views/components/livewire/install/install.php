<?php

declare(strict_types=1);

use Devanox\Core\Helpers\EnvEditor;
use Devanox\Core\Helpers\InstallerInfo;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
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

    public function mount(): void
    {
        InstallerInfo::create([
            'version' => config('app.version'),
        ]);

        InstallerInfo::setStatus(InstallerInfo::NOT_STARTED);

        $this->steps = [
            'home' => __('core::install.steps.home.title'),
            'requirements' => __('core::install.steps.requirements.title'),
            'permissions' => __('core::install.steps.permissions.title'),
            'database' => __('core::install.steps.database.title'),
            'migrations' => __('core::install.steps.migrations.title'),
            'admin' => __('core::install.steps.admin.title'),
            'finish' => __('core::install.steps.finish.title'),
        ];

        $this->nextStep = $this->availableNextStep($this->activeStep);
    }

    public function setNextStep(string $step): void
    {
        $this->nextStep = $this->availableNextStep($step);
    }

    public function unsetNextStep(): void
    {
        $this->nextStep = null;
    }

    public function goToStep(string $step): void
    {
        $this->activeStep = $step;
        $this->nextStep = null;
    }

    public function availableNextStep(string $step): int|string|null
    {
        $keys = array_keys($this->steps);
        $currentIndex = array_search($step, $keys, true);

        if ($currentIndex !== false && isset($keys[$currentIndex + 1])) {
            return $keys[$currentIndex + 1];
        }

        return null;
    }

    public function finish(): void
    {
        InstallerInfo::setStatus(InstallerInfo::FINALIZING);

        EnvEditor::insertMultiple([
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ]);

        InstallerInfo::setStatus(InstallerInfo::COMPLETED);
        File::put(storage_path('installed'), '');

        $this->redirectRoute('login', navigate: true);
    }
};
