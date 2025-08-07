<?php

namespace Devanox\Core\Commands;

use Devanox\Core\Models\License;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LicenseCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devanox:license-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check application license status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $licenses = License::get();

        foreach ($licenses as $license) {
            if ($license->isValid()) {
                $this->checkLicenseStatus($license);
            }
        }

        return Command::SUCCESS;
    }

    private function checkLicenseStatus(License $license): void
    {
        $verifyUrl = config('core.url.server');

        if (empty($verifyUrl)) {
            $this->error(__('core::app.exception.server_url'));
            return;
        }

        $verifyUrl .= '/api/purchase/verify';

        $response = Http::acceptJson()->post($verifyUrl, [
            'license' => $license->key,
            'domain' => $license->domain,
            'ip' => $license->ip,
            'version' => config('app.version'),
        ]);

        if ($response->failed()) {
            $license->delete();
        }

        if ($response->json('status') !== 'success') {
            $license->delete();
        }

        $responseData = $response->object();
        if ($responseData->status !== 'success') {
            $license->delete();
        }

        $license->purchase_code = $responseData->data->purchase_code;
        $license->type = $responseData->data->type;
        $license->purchase_at = $responseData->data->purchase_at;
        $license->support_until = $responseData->data->support_until;
        $license->save();
    }
}
