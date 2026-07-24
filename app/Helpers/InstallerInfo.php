<?php

declare(strict_types=1);

namespace Devanox\Core\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Fluent;

final class InstallerInfo
{
    public const string NOT_STARTED = 'not_started';

    // Requirements & Permissions
    public const string REQUIREMENTS_CHECKING = 'requirements_checking';

    public const string REQUIREMENTS_PASSED = 'requirements_passed';

    public const string PERMISSIONS_CHECKING = 'permissions_checking';

    public const string PERMISSIONS_PASSED = 'permissions_passed';

    // Database Setup
    public const string DB_CONFIGURING = 'db_configuring';

    public const string DB_CONFIGURED = 'db_configured';

    // Migrations
    public const string MIGRATING = 'migrating';

    public const string MIGRATED = 'migrated';

    // Admin Account & Finalization
    public const string ADMIN_CREATING = 'admin_creating';

    public const string ADMIN_CREATED = 'admin_created';

    public const string FINALIZING = 'finalizing';

    public const string COMPLETED = 'completed';

    // Error States
    public const string ERROR = 'error';

    public const string FAILED = 'failed';

    /**
     * Get the path to the installation information file.
     */
    public static function filePath(): string
    {
        return storage_path('install.json');
    }

    /**
     * Create the installation information file with optional initial data.
     *
     * @return Fluent<string, mixed>
     */

    /**
     * @param  array<string, mixed>  $data
     * @return Fluent<string, mixed>
     */
    public static function create(array $data = []): Fluent
    {
        $installFile = self::filePath();

        File::put(
            $installFile,
            (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            true,
        );

        return fluent($data);
    }

    /**
     * Retrieve the installation data as a Fluent instance.
     *
     * @return Fluent<string, mixed>
     */
    public static function data(): Fluent
    {
        $installFile = self::filePath();

        if (! File::exists($installFile)) {
            return fluent([]);
        }

        $installData = json_decode(File::sharedGet($installFile), true);

        return fluent(is_array($installData) ? $installData : []);
    }

    /**
     * Get a specific value from the installation data.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $data = self::data();

        if ($data->has($key)) {
            return $data->get($key, $default);
        }

        return $default;
    }

    /**
     * Perform a bulk update to minimize disk I/O.
     *
     * @return Fluent<string, mixed>
     */

    /**
     * @param  callable(Fluent<string, mixed>): void  $callback
     * @return Fluent<string, mixed>
     */
    public static function update(callable $callback): Fluent
    {
        $data = self::data();
        $callback($data);

        File::put(
            self::filePath(),
            (string) json_encode($data->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            true,
        );

        return $data;
    }

    /**
     * Set a specific value in the installation data.
     *
     * @return Fluent<string, mixed>
     */
    public static function set(string $key, mixed $value): Fluent
    {
        return self::update(function (Fluent $data) use ($key, $value): void {
            $data->set($key, $value);
        });
    }

    /**
     * Get the current installation status.
     */
    public static function getStatus(): string
    {
        /** @var string $status */
        $status = self::get('status', self::NOT_STARTED);

        return $status;
    }

    /**
     * Set the current installation status and record the timestamp.
     *
     * @return Fluent<string, mixed>
     */
    public static function setStatus(string $status): Fluent
    {
        return self::update(function (Fluent $data) use ($status): void {
            $data->set('status', $status);
            $data->set($status . '_at', now()->toDateTimeString());

            self::addLogEntry($data, 'Status updated to: ' . $status, 'info');
        });
    }

    /**
     * Mark the installation as failed with an error message.
     *
     * @return Fluent<string, mixed>
     */
    public static function setError(string $message): Fluent
    {
        return self::update(function (Fluent $data) use ($message): void {
            $data->set('error_message', $message);
            $data->set('status', self::ERROR);
            $data->set(self::ERROR . '_at', now()->toDateTimeString());

            self::addLogEntry($data, $message, 'error');
            self::addLogEntry($data, 'Status updated to: ' . self::ERROR, 'info');
        });
    }

    /**
     * Get the last installation error message.
     */
    public static function getError(): ?string
    {
        /** @var string|null $error */
        $error = self::get('error_message');

        return $error;
    }

    /**
     * Set the overall installation progress (0-100).
     *
     * @return Fluent<string, mixed>
     */
    public static function setProgress(int $percentage): Fluent
    {
        return self::set('progress', max(0, min(100, $percentage)));
    }

    /**
     * Get the current installation progress percentage.
     */
    public static function getProgress(): int
    {
        /** @var int|numeric-string $progress */
        $progress = self::get('progress', 0);

        return (int) $progress;
    }

    /**
     * Get the timestamp for when a specific status was reached.
     */
    public static function getTimestamp(string $status): ?string
    {
        /** @var string|null $timestamp */
        $timestamp = self::get($status . '_at');

        return $timestamp;
    }

    /**
     * Check if the application is fully installed.
     */
    public static function isInstalled(): bool
    {
        return self::getStatus() === self::COMPLETED;
    }

    /**
     * Append a message to the installation history log.
     *
     * @return Fluent<string, mixed>
     */
    public static function log(string $message, string $level = 'info'): Fluent
    {
        return self::update(function (Fluent $data) use ($message, $level): void {
            self::addLogEntry($data, $message, $level);
        });
    }

    /**
     * Retrieve the full installation history log.
     */

    /**
     * @return array<int, array{level: string, message: string, timestamp: string}>
     */
    public static function getLogs(): array
    {
        /** @var array<int, array{level: string, message: string, timestamp: string}> $logs */
        $logs = self::get('logs', []);

        return $logs;
    }

    /**
     * Remove the installation information file and clean up.
     */
    public static function remove(): void
    {
        $installFile = self::filePath();

        if (File::exists($installFile)) {
            File::delete($installFile);
        }
    }

    /**
     * Add a formatted log entry to the data structure.
     */

    /**
     * @param  Fluent<string, mixed>  $data
     */
    private static function addLogEntry(Fluent $data, string $message, string $level): void
    {
        /** @var array<int, array{level: string, message: string, timestamp: string}> $logs */
        $logs = $data->get('logs', []);

        $logs[] = [
            'level' => $level,
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
        ];
        $data->set('logs', $logs);
    }
}
