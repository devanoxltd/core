<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Helpers\App;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Activation extends Component
{
    #[Locked]
    public bool $isActivated = false;

    public ?string $licenseKey = null;

    public function activate()
    {
        $this->validate([
            'licenseKey' => 'required|string',
        ]);

        try {
            App::verifyLicense($this->licenseKey);

            Cache::forget('license.valid');

            $this->isActivated = true;
        } catch (\Exception $e) {
            $this->addError('licenseKey', $e->getMessage());
        }
    }

    public function check() {
        if (isLicenseValid()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function openApp()
    {
        $this->redirectRoute('login', navigate: true);
    }

    public function render()
    {
        return view('core::livewire.activation');
    }
}
