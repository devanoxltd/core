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

            info([
                $responseData->message,
                'data' => $responseData->data->id,
            ]);

            $licecnse = Licence::query()
                ->where('key', $this->licenseKey)
                ->first() ?: new Licence();

            $licecnse->key = $responseData->data->id;
            $licecnse->purchase_code = $responseData->data->purchase_code;
            $licecnse->type = $responseData->data->type;
            $licecnse->purchase_at = $responseData->data->purchase_at;
            $licecnse->support_until = $responseData->data->support_until;
            $licecnse->save();

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
