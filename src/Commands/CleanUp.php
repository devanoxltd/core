<?php

namespace Devanox\Core\Commands;

use Illuminate\Console\Command;

class CleanUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // delete logs
        $this->clearLogs();

        $this->info('Cleanup completed successfully.');

        return Command::SUCCESS;
    }

    protected function clearLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            $this->info('Logs cleared successfully.');
        } else {
            $logFiles = glob(storage_path('logs/*.log'));
            if (empty($logFiles)) {
                $this->error('No log files found.');
            } else {
                $this->deleteOldLogs($logFiles);
            }
        }
    }

    /**
     * @param  array <string>  $logFiles
     */
    protected function deleteOldLogs(array $logFiles, int $days = 7): void
    {
        $threshold = time() - ($days * 86400);

        foreach ($logFiles as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $this->info('Old log file deleted: ' . $file);
            }
        }
    }
}
