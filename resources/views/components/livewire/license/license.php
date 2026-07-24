<?php

declare(strict_types=1);

use Devanox\Core\Helpers\App;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public bool $isActivated = false;

    public ?string $licenseKey = null;

    public function mount(): void
    {
        $this->check();
    }

    public function activate(): void
    {
        $this->validate([
            'licenseKey' => 'required|string',
        ]);

        try {
            App::verifyLicense($this->licenseKey);

            Cache::forget('license.valid.core');

            $this->isActivated = true;
        } catch (Exception $exception) {
            $this->addError('licenseKey', $exception->getMessage());
        }
    }

    public function check(): void
    {
        if (isLicenseValid()) {
            $this->redirectRoute('login', navigate: true);
        }
    }
};
