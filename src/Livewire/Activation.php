<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Models\License;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            $verifyUrl = config('core.url.server');

            if (empty($verifyUrl)) {
                throw new \Exception(__('core::install.steps.activation.error'));
            }

            $verifyUrl .= '/api/purchase/verify';

            $response = Http::acceptJson()->post($verifyUrl, [
                'id' => config('app.id'),
                'version' => config('app.version'),
                'license' => $this->licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
            ]);

            if ($response->failed()) {
                throw new \Exception($response->json('message', __('core::install.steps.activation.error')));
            }

            $responseData = $response->object();

            if ($responseData->status !== 'success') {
                throw new \Exception($responseData->message ?? __('core::install.steps.activation.error'));
            }

            $license = License::query()
                ->where('key', $this->licenseKey)
                ->first() ?: new License();

            $license->key = $responseData->data->id;
            $license->purchase_code = $responseData->data->purchase_code;
            $license->type = $responseData->data->type;
            $license->purchase_at = $responseData->data->purchase_at;
            $license->support_until = $responseData->data->support_until;
            $license->save();

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
