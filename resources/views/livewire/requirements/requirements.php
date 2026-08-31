<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public $requirements = [];

    public function mount(): void
    {
        $this->checkRequirements();
    }

    public function checkRequirements(): void
    {
        InstallerInfo::setStatus(InstallerInfo::REQUIREMENTS_CHECKING);

        $requirements = collect();

        $requirements->push([
            'name' => 'PHP ' . config('core.php') . ' or higher.',
            'status' => version_compare(PHP_VERSION, config('core.php'), '>='),
        ]);

        $maxExecutionTime = (int) ini_get('max_execution_time');
        $requirements->push([
            'name' => __('core::install.steps.requirements.max_execution_time'),
            'status' => $maxExecutionTime === 0 || $maxExecutionTime >= config('core.max_execution_time'),
        ]);

        $allowUrlFopen = ini_get('allow_url_fopen');
        $allowUrlFopen = in_array($allowUrlFopen, ['1', 'On', 'true', true], true);

        $requirements->push([
            'name' => 'allow_url_fopen',
            'status' => $allowUrlFopen,
        ]);

        foreach (config('core.extensions', []) as $extension) {
            $requirements->push([
                'name' => $extension,
                'status' => extension_loaded($extension),
            ]);
        }

        foreach (config('core.functions', []) as $function) {
            $requirements->push([
                'name' => $function,
                'status' => function_exists($function),
            ]);
        }

        $this->requirements = $requirements->toArray();

        $status = $requirements->every(fn (array $requirement): bool => $requirement['status'] === true);

        if ($status) {
            InstallerInfo::setStatus(InstallerInfo::REQUIREMENTS_PASSED);
            $this->dispatch('stepReady', step: 'requirements')->to('core::install');
        }
    }
};
