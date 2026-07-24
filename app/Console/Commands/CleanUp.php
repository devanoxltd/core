<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'app:clean-up', description: 'Clean up the application logs')]
#[Description('Clean up the application logs')]
#[Signature('app:clean-up {--days=7 : The number of days to retain old logs} {--dry-run : Run the command without actually modifying or deleting any files}')]
final class CleanUp extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        info('Starting application cleanup...');

        if ($this->option('dry-run')) {
            warning('DRY RUN MODE: No files will actually be modified or deleted.');
        }

        // delete logs
        $status = $this->clearLogs();

        if ($status === Command::SUCCESS) {
            info('Cleanup completed successfully.');
        }

        return $status;
    }

    private function clearLogs(): int
    {
        $logPath = storage_path('logs');
        $logFiles = File::glob($logPath . '/*.log') ?: [];

        if (empty($logFiles)) {
            info('No log files found.');

            return Command::SUCCESS;
        }

        $activeLogs = ['laravel.log', 'browser.log'];
        $clearedSuccessfully = true;

        foreach ($activeLogs as $activeLogFile) {
            $logFilePath = storage_path('logs/' . $activeLogFile);

            if (File::exists($logFilePath)) {
                if ($this->option('dry-run')) {
                    note('Would clear: ' . $activeLogFile);
                    $status = true;
                } else {
                    $status = spin(
                        fn (): bool => File::put($logFilePath, '') !== false,
                        'Clearing ' . $activeLogFile,
                    );

                    if ($status) {
                        info('Cleared ' . $activeLogFile);
                    } else {
                        error('Failed to clear ' . $activeLogFile);
                    }
                }

                $clearedSuccessfully = $clearedSuccessfully && $status;
            }
        }

        $logFiles = array_filter($logFiles, fn (mixed $file): bool => is_string($file) && ! in_array(basename($file), $activeLogs, true));

        if ($logFiles !== []) {
            $deleteStatus = $this->deleteOldLogs(array_values(array_filter(array_map(strval(...), $logFiles))), (int) $this->option('days'));

            return $clearedSuccessfully && $deleteStatus === Command::SUCCESS
                ? Command::SUCCESS
                : Command::FAILURE;
        }

        return $clearedSuccessfully ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param  array<int, string>  $logFiles
     */
    private function deleteOldLogs(array $logFiles, int $days = 7): int
    {
        $threshold = now()->subDays($days)->timestamp;
        $deletedFiles = [];

        foreach ($logFiles as $file) {
            if (File::lastModified($file) < $threshold) {
                $deletedFiles[] = $file;
            }
        }

        if ($deletedFiles !== []) {
            if ($this->option('dry-run')) {
                note(sprintf('Would delete %d old log file(s) (older than %d days):', count($deletedFiles), $days));

                foreach ($deletedFiles as $file) {
                    note('  → ' . basename($file));
                }

                return Command::SUCCESS;
            }

            $success = spin(
                fn () => File::delete($deletedFiles),
                sprintf('Deleting %d old log file(s) (older than %d days)', count($deletedFiles), $days),
            );

            if ($success) {
                info(sprintf('Deleted %d old log file(s)', count($deletedFiles)));
            } else {
                error('Failed to delete old log file(s)');
            }

            return $success ? Command::SUCCESS : Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
