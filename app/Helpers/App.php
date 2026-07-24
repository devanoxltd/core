<?php

declare(strict_types=1);

namespace Devanox\Core\Helpers;

use Devanox\Core\Models\License;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

final class App
{
    /**
     * @return array{0: License, 1: object}
     *
     * @throws Exception
     */
    public static function verifyLicense(string $licenseKey, ?int $moduleId = null): array
    {
        $verifyUrl = config('core.url.server');

        if (! is_string($verifyUrl) || mb_trim($verifyUrl) === '' || mb_trim($verifyUrl) === '0') {
            throw new Exception(__('core::app.exception.server_url'));
        }

        $verifyUrl = mb_trim($verifyUrl);

        $verifyUrl = mb_rtrim($verifyUrl, '/') . '/api/purchase/verify';

        $appUrl = config('app.url');
        $parsedHost = is_string($appUrl) ? parse_url($appUrl, PHP_URL_HOST) : null;

        $response = Http::acceptJson()
            ->timeout(10)
            ->retry(3, 100, throw: false)
            ->post($verifyUrl, [
                'application_id' => config('app.id'),
                'version' => config('app.version', '1.0.0'),
                'license' => $licenseKey,
                'domain' => $parsedHost ?? request()->getHost(),
                'ip' => request()->server('SERVER_ADDR') ?? request()->ip(),
                'module_id' => $moduleId,
            ]);

        if ($response->failed()) {
            $message = $response->json('message');
            $message = is_string($message) ? $message : __('core::app.exception.error');

            throw new Exception($message);
        }

        /** @var object{status: string, data: object{id: string, purchase_code: string, type: string, purchase_at: string, support_until: string, status: string, module: object{name: string}}, message: string}|null $responseData */
        $responseData = $response->object();

        if ($responseData === null || ! isset($responseData->status) || $responseData->status !== 'success' || empty($responseData->data)) {
            $message = $responseData->message ?? __('core::app.exception.error');

            throw new Exception(is_string($message) ? $message : __('core::app.exception.error'));
        }

        $data = $responseData->data;
        $module = property_exists($data, 'module') ? $data->module : null;
        $moduleName = is_object($module) && property_exists($module, 'name') ? $module->name : null;
        $moduleName = is_string($moduleName) ? $moduleName : null;

        $license = License::query()
            ->unless(in_array($moduleName, [null, '', '0'], true), fn (Builder $query): Builder => $query->isModule((string) $moduleName))
            ->when(in_array($moduleName, [null, '', '0'], true), fn (Builder $query): Builder => $query->isCore())
            ->where('key', $licenseKey)
            ->firstOrNew();

        $license->fill([
            'key' => $data->id ?? $licenseKey,
            'purchase_code' => $data->purchase_code ?? null,
            'type' => $data->type ?? 'regular',
            'purchase_at' => $data->purchase_at ?? now(),
            'support_until' => $data->support_until ?? null,
            'is_module' => $moduleId !== null,
            'module_name' => $moduleName,
            'status' => $data->status ?? 'valid',
            'last_checked_at' => now(),
        ])->save();

        return [$license, $responseData];
    }
}
