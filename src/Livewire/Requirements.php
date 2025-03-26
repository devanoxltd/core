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
            'name' => 'PHP ' . config('core.minPhpVersion') . ' or higher. (' . config('core.recommendedPhpVersion') . ' recommended)',
            'status' => version_compare(PHP_VERSION, config('core.minPhpVersion'), '>='),
        ]);

        foreach (config('core.requirements.php', []) as $extension) {
            $requirements->push([
                'name' => $extension,
                'status' => extension_loaded($extension),
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
