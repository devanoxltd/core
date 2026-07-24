<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Devanox\Core\Models\License;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'devanox:license-check', description: 'Check application license status')]
#[Description('Check application license status')]
#[Signature('devanox:license-check {--force : Force check all licenses ignoring last checked time}')]
final class LicenseCheck extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        info('Checking licenses...');

        // Clean up empty/garbage licenses in a single query
        $deleted = License::query()->whereNull('key')->orWhere('key', '')->delete();
        $deletedCount = is_numeric($deleted) ? (int) $deleted : 0;

        if ($deletedCount > 0) {
            warning(sprintf('Deleted %d garbage license(s) with no key.', $deletedCount));
        }

        $licenses = License::query()->cursor();
        $checkedCount = 0;
        $hasErrors = false;

        foreach ($licenses as $license) {
            // Skip if checked in the last 24 hours unless --force is passed
            if (! $this->option('force') && $license->last_checked_at?->greaterThan(now()->subDay())) {
                warning(sprintf('License [%s] SKIPPED (RECENT)', $license->key));

                continue;
            }

            $success = $this->checkLicenseStatus($license);

            if (! $success) {
                $hasErrors = true;
            }

            $checkedCount++;
        }

        if ($checkedCount === 0) {
            warning('No valid licenses found requiring a check.');
        } else {
            info(sprintf('Finished checking %d license(s).', $checkedCount));
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Verify the license status with the central server.
     */
    private function checkLicenseStatus(License $license): bool
    {
        $verifyUrl = config('core.url.server');

        if (! is_string($verifyUrl)) {
            error(__('core::app.exception.server_url'));

            return false;
        }

        $verifyUrl = mb_rtrim($verifyUrl, '/') . '/api/purchase/verify';

        try {
            $appUrl = config('app.url');
            $parsedHost = is_string($appUrl) ? parse_url($appUrl, PHP_URL_HOST) : null;
            $domain = $license->domain ?? $parsedHost ?? request()->getHost();

            $response = Http::acceptJson()
                ->timeout(10)
                ->retry(3, 100, throw: false)
                ->post($verifyUrl, [
                    'license' => $license->key,
                    'domain' => $domain,
                    'ip' => $license->ip ?? request()->server('SERVER_ADDR') ?? request()->ip(),
                    'version' => config('app.version', '1.0.0'),
                ]);

            // Don't invalidate if the server is just down
            if ($response->serverError()) {
                warning(sprintf('License server unavailable (5xx error). Skipping check for [%s].', $license->key));

                return false;
            }

            if ($response->failed() || $response->json('status') !== 'success') {
                $license->update(['status' => 'invalid']);
                warning(sprintf('License [%s] has been invalidated.', $license->key));

                return false;
            }

            /** @var object{data: object{status?: string, purchase_code?: string, type?: string, purchase_at?: string, support_until?: string}}|null $responseObj */
            $responseObj = $response->object();
            $data = is_object($responseObj) && property_exists($responseObj, 'data') ? $responseObj->data : null;

            if (empty($data)) {
                $license->update(['status' => 'invalid']);
                warning(sprintf('Invalid response data for license [%s]. It has been invalidated.', $license->key));

                return false;
            }

            $license->update([
                'status' => $data->status ?? 'invalid',
                'purchase_code' => $data->purchase_code ?? $license->purchase_code,
                'type' => $data->type ?? $license->type,
                'purchase_at' => $data->purchase_at ?? $license->purchase_at,
                'support_until' => $data->support_until ?? $license->support_until,
                'last_checked_at' => now(),
            ]);

            info(sprintf('License [%s] checked successfully', $license->key));

            return true;
        } catch (Throwable $throwable) {
            error(sprintf('Failed to check license [%s]: %s', $license->key, $throwable->getMessage()));

            Log::error('License check failed', [
                'license' => $license->key,
                'exception' => $throwable->getMessage(),
            ]);

            return false;
        }
    }
}
