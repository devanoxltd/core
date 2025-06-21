<?php

namespace Devanox\Core\Helpers;

use Devanox\Core\Models\License;
use Exception;
use Illuminate\Support\Facades\Http;

class App
{
    public static function verifyLicense(string $licenseKey, ?int $moduleId = null): array
    {
        $verifyUrl = config('core.url.server');

        if (empty($verifyUrl)) {
            throw new Exception(__('core::app.exception.server_url'));
        }

        $verifyUrl .= '/api/purchase/verify';

        $response = Http::acceptJson()->post($verifyUrl, [
            'application_id' => config('app.id'),
            'version' => config('app.version'),
            'license' => $licenseKey,
            'domain' => request()->getHost(),
            'ip' => request()->ip(),
            'module_id' => $moduleId,
        ]);

        if ($response->failed()) {
            throw new Exception($response->json('message', __('core::app.exception.error')));
        }

        $responseData = $response->object();

        if ($responseData->status !== 'success') {
            throw new Exception($responseData->message ?? __('core::app.exception.error'));
        }

        $license = License::query()
            ->when($responseData->data->module, function ($query) use ($responseData) {
                return $query->where('is_module', true)
                    ->where('module_name', $responseData->data->module?->name);
            })
            ->where('key', $licenseKey)
            ->first() ?: new License();

        $license->key = $responseData->data->id;
        $license->purchase_code = $responseData->data->purchase_code;
        $license->type = $responseData->data->type;
        $license->purchase_at = $responseData->data->purchase_at;
        $license->support_until = $responseData->data->support_until;
        $license->is_module = $moduleId ? true : false;
        $license->module_name = $responseData->data->module?->name;
        $license->save();

        return [$license, $responseData];
    }
}
