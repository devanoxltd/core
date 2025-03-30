<?php

namespace Devanox\Core\Commands;

use Devanox\Core\Models\Licence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LicenceCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devanox:licence-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check application licence status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $licences = Licence::get();

        foreach ($licences as $licence) {
            if ($licence->isValid()) {
                $this->checkLicenceStatus($licence);
            }
        }

        return Command::SUCCESS;
    }

    private function checkLicenceStatus(Licence $licence): void
    {
        // TODO : update this URL to your production URL
        // $verifyUrl = 'https://devanox.com';
        $verifyUrl = 'https://devanox-activate.test';
        $verifyUrl .= '/api/purchase/verify';

        $response = Http::acceptJson()->post($verifyUrl, [
            'licence' => $licence->key,
            'domain' => $licence->domain,
            'ip' => $licence->ip,
            'version' => config('app.version'),
        ]);

        if ($response->failed()) {
            $licence->delete();
        }

        if ($response->json('status') !== 'success') {
            $licence->delete();
        }

        $responseData = $response->object();
        if ($responseData->status !== 'success') {
            $licence->delete();
        }

        $licence->purchase_code = $responseData->data->purchase_code;
        $licence->type = $responseData->data->type;
        $licence->purchase_at = $responseData->data->purchase_at;
        $licence->support_until = $responseData->data->support_until;
        $licence->save();
    }
}
