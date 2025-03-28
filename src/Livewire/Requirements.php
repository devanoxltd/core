<?php

namespace Devanox\Core\Livewire;

use Livewire\Component;

class Requirements extends Component
{
    public $requirements = [];

    public function mount()
    {
        $this->checkRequirements();
    }

    public function checkRequirements()
    {
        $requirements = collect();

        $requirements->push([
            'name' => 'PHP ' . config('core.phpVersion') . ' or higher.',
            'status' => version_compare(PHP_VERSION, config('core.phpVersion'), '>='),
        ]);

        $requirements->push([
            'name' => __('core::install.steps.requirements.max_execution_time'),
            'status' => ini_get('max_execution_time') >= config('core.requirements.max_execution_time'),
        ]);

        $allowUrlFopen = ini_get('allow_url_fopen');
        $allowUrlFopen = $allowUrlFopen === '1' || $allowUrlFopen === 'On' || $allowUrlFopen === 'true' || $allowUrlFopen === true;
        $requirements->push([
            'name' => 'allow_url_fopen',
            'status' => $allowUrlFopen,
        ]);

        foreach (config('core.requirements.php', []) as $extension) {
            $requirements->push([
                'name' => $extension,
                'status' => extension_loaded($extension),
            ]);
        }

        foreach (config('core.requirements.php_function', []) as $function) {
            $requirements->push([
                'name' => $function,
                'status' => function_exists($function),
            ]);
        }

        $this->requirements = $requirements->toArray();

        $status = $requirements->every(function ($requirement) {
            return $requirement['status'] === true;
        });

        if ($status) {
            $this->dispatch('stepReady', step: 'requirements')->to(Install::class);
        }
    }

    public function render()
    {
        return view('core::livewire.requirements');
    }
}
