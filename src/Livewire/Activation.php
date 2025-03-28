<?php

namespace Devanox\Core\Livewire;

use Livewire\Component;

class Activation extends Component
{
    public $isActivated = false;

    public $licenseKey = '';

    public function activate()
    {
        $this->validate([
            'licenseKey' => 'required|string',
        ]);

        try {
            // Simulate activation process
            $this->isActivated = true;
            $this->dispatch('stepReady', step: 'activation')->to(Install::class);
        } catch (\Exception $e) {
            // add error message to the error bag
            $this->addError('licenseKey', $e->getMessage());
        }
    }

    public function render()
    {
        return view('core::livewire.activation');
    }
}
