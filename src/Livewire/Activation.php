<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Models\Licence;
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
                'licence' => $this->licenseKey,
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

            $licence = Licence::query()
                ->where('key', $this->licenseKey)
                ->first() ?: new Licence();

            $licence->key = $responseData->data->id;
            $licence->purchase_code = $responseData->data->purchase_code;
            $licence->type = $responseData->data->type;
            $licence->purchase_at = $responseData->data->purchase_at;
            $licence->support_until = $responseData->data->support_until;
            $licence->save();

            Cache::forget('licence.valid');

            $this->isActivated = true;
        } catch (\Exception $e) {
            $this->addError('licenseKey', $e->getMessage());
        }
    }

    public function check() {
        if (isLicenceValid()) {
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
