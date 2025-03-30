<?php

namespace Devanox\Core\Livewire;

use Devanox\Core\Models\Licence;
use Illuminate\Support\Facades\Http;
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
            // TODO : update this URL to your production URL
            // $verifyUrl = 'https://devanox.com';
            $verifyUrl = 'https://devanox-activate.test';
            $verifyUrl .= '/api/purchase/verify';

            $response = Http::acceptJson()->post($verifyUrl, [
                'licence' => $this->licenseKey,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'version' => config('app.version'),
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

            $this->isActivated = true;
        } catch (\Exception $e) {
            $this->addError('licenseKey', $e->getMessage());
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
