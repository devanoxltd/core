<?php

declare(strict_types=1);

namespace Devanox\Core\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Fluent;

final class EnvEditor
{
    /**
     * Get the full path to the environment file.
     */
    public static function filePath(string $filename = '.env'): string
    {
        return base_path($filename);
    }

    /**
     * Check if the environment file exists.
     */
    public static function fileExists(string $filename = '.env'): bool
    {
        return File::exists(self::filePath($filename));
    }

    /**
     * Retrieve all variables from the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function data(bool $includeCommented = false, string $filename = '.env'): Fluent
    {
        $lines = self::getLines($filename);
        $data = [];

        foreach ($lines as $line) {
            $trimmed = mb_trim((string) $line);
            $isCommented = mb_strpos($trimmed, '#') === 0;

            if ($isCommented && ! $includeCommented) {
                continue;
            }

            $lineContent = $isCommented ? mb_ltrim($trimmed, '# ') : $trimmed;

            if (str_contains($lineContent, '=')) {
                [$key, $value] = explode('=', $lineContent, 2);
                $key = mb_trim($key);

                if ($isCommented) {
                    if (! isset($data[$key])) {
                        $data[$key] = self::stripQuotes(mb_trim($value));
                    }
                } else {
                    $data[$key] = self::stripQuotes(mb_trim($value));
                }
            }
        }

        return fluent($data);
    }

    /**
     * Check if a specific key is commented out in the environment file.
     */
    public static function isKeyCommented(string $key): bool
    {
        $lines = self::getLines();

        return array_any($lines, fn (string $line): bool => (bool) preg_match('/^\s*#\s*' . preg_quote($key, '/') . '\s*=/', $line));
    }

    /**
     * Get the value of a specific key from the environment file.
     */
    public static function get(string $key, mixed $default = null, bool $includeCommented = false, string $filename = '.env'): mixed
    {
        $data = self::data($includeCommented, $filename);

        if ($data->has($key)) {
            return $data->get($key, $default);
        }

        return $default;
    }

    /**
     * Insert a new key-value pair into the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function insert(string $key, mixed $value): Fluent
    {
        return self::insertMultiple([$key => $value]);
    }

    /**
     * Insert a commented key-value pair into the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function insertCommented(string $key, mixed $value, ?string $searchKey = null, ?string $position = null): Fluent
    {
        self::insertMultiple([$key => $value], $searchKey, $position);

        return self::comment($key);
    }

    /**
     * Remove a key from the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function remove(string $key): Fluent
    {
        return self::removeMultiple([$key]);
    }

    /**
     * Comment out an existing key in the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function comment(string $key): Fluent
    {
        if (! self::fileExists()) {
            return self::data();
        }

        $lines = self::getLines();
        $changed = false;

        foreach ($lines as &$line) {
            // Match only uncommented key
            if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', (string) $line)) {
                $line = '# ' . $line;
                $changed = true;

                break;
            }
        }

        if ($changed) {
            self::saveLines($lines);
        }

        return self::data();
    }

    /**
     * Uncomment an existing key in the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function uncomment(string $key): Fluent
    {
        if (! self::fileExists()) {
            return self::data();
        }

        $lines = self::getLines();
        $changed = false;

        foreach ($lines as &$line) {
            // Match only commented key
            if (preg_match('/^\s*#\s*' . preg_quote($key, '/') . '\s*=/', (string) $line)) {
                $line = (string) preg_replace('/^\s*#\s*/', '', (string) $line);
                $changed = true;

                break;
            }
        }

        if ($changed) {
            self::saveLines($lines);
        }

        return self::data();
    }

    /**
     * Insert multiple key-value pairs into the environment file.
     */

    /** @param array<string, mixed> $newData
     * @return Fluent<string, mixed> */
    public static function insertMultiple(array $newData, ?string $searchKey = null, ?string $position = null): Fluent
    {
        foreach ($newData as $key => $value) {
            $newData[$key] = self::formatValue($value);
        }

        if (! self::fileExists()) {
            $lines = [];

            foreach ($newData as $key => $value) {
                $lines[] = sprintf('%s=%s', $key, $value);
            }

            self::saveLines($lines);

            return self::data();
        }

        $lines = self::getLines();
        $newLines = [];
        $processedKeys = [];
        $actionTaken = false;

        foreach ($lines as $line) {
            $isSearchKey = $searchKey !== null && preg_match('/^\s*#?\s*' . preg_quote($searchKey, '/') . '\s*=/', (string) $line);

            $matchedNewKey = null;

            foreach ($newData as $key => $value) {
                if (preg_match('/^\s*#?\s*' . preg_quote((string) $key, '/') . '\s*=/', (string) $line)) {
                    $matchedNewKey = $key;

                    break;
                }
            }

            if (! $actionTaken && $isSearchKey && $position !== null) {
                if ($position === 'before') {
                    foreach ($newData as $key => $value) {
                        $newLines[] = sprintf('%s=%s', $key, $value);
                        $processedKeys[] = $key;
                    }

                    if ($matchedNewKey === null || $matchedNewKey !== $searchKey) {
                        $newLines[] = $line;
                    }
                } else {
                    if ($matchedNewKey === null || $matchedNewKey !== $searchKey) {
                        $newLines[] = $line;
                    }

                    foreach ($newData as $key => $value) {
                        $newLines[] = sprintf('%s=%s', $key, $value);
                        $processedKeys[] = $key;
                    }
                }

                $actionTaken = true;
            } elseif ($matchedNewKey !== null) {
                if ($position === null && ! in_array($matchedNewKey, $processedKeys, true)) {
                    $newLines[] = sprintf('%s=%s', $matchedNewKey, $newData[$matchedNewKey]);
                    $processedKeys[] = $matchedNewKey;
                }
            } else {
                $newLines[] = $line;
            }
        }

        foreach ($newData as $key => $value) {
            if (! in_array($key, $processedKeys, true)) {
                $newLines[] = sprintf('%s=%s', $key, $value);
            }
        }

        self::saveLines($newLines);

        return self::data();
    }

    /**
     * Check if a specific key exists in the environment file.
     */
    public static function has(string $key, bool $includeCommented = false): bool
    {
        return self::data($includeCommented)->has($key);
    }

    /**
     * Add an empty line to the environment file.
     */
    public static function addEmptyLine(?string $searchKey = null, ?string $position = null): void
    {
        self::insertRawLine('', $searchKey, $position);
    }

    /**
     * Add a comment line to the environment file.
     */
    public static function addCommentLine(string $comment, ?string $searchKey = null, ?string $position = null): void
    {
        self::insertRawLine('# ' . mb_ltrim($comment, '# '), $searchKey, $position);
    }

    /**
     * Rename an existing key in the environment file.
     *
     * @return Fluent<string, mixed>
     */
    public static function renameKey(string $oldKey, string $newKey): Fluent
    {
        if (! self::fileExists()) {
            return self::data();
        }

        $lines = self::getLines();
        $changed = false;

        foreach ($lines as &$line) {
            if (preg_match('/^(\s*#?\s*)' . preg_quote($oldKey, '/') . '(\s*=.*)$/', (string) $line, $matches)) {
                $line = $matches[1] . $newKey . $matches[2];
                $changed = true;

                break;
            }
        }

        if ($changed) {
            self::saveLines($lines);
        }

        return self::data();
    }

    /**
     * Insert a new key-value pair after a specific key.
     *
     * @return Fluent<string, mixed>
     */
    public static function insertAfter(string $searchKey, string $newKey, mixed $newValue): Fluent
    {
        return self::insertMultiple([$newKey => $newValue], $searchKey, 'after');
    }

    /**
     * Insert a new key-value pair before a specific key.
     *
     * @return Fluent<string, mixed>
     */
    public static function insertBefore(string $searchKey, string $newKey, mixed $newValue): Fluent
    {
        return self::insertMultiple([$newKey => $newValue], $searchKey, 'before');
    }

    /**
     * Move an existing key after a specific key.
     *
     * @return Fluent<string, mixed>
     */
    public static function moveAfter(string $searchKey, string $keyToMove): Fluent
    {
        if (! self::has($keyToMove, true)) {
            return self::data();
        }

        $isActive = self::has($keyToMove);
        $value = self::get($keyToMove, null, true);

        self::insertAfter($searchKey, $keyToMove, $value);

        if (! $isActive) {
            self::comment($keyToMove);
        }

        return self::data();
    }

    /**
     * Move an existing key before a specific key.
     *
     * @return Fluent<string, mixed>
     */
    public static function moveBefore(string $searchKey, string $keyToMove): Fluent
    {
        if (! self::has($keyToMove, true)) {
            return self::data();
        }

        $isActive = self::has($keyToMove);
        $value = self::get($keyToMove, null, true);

        self::insertBefore($searchKey, $keyToMove, $value);

        if (! $isActive) {
            self::comment($keyToMove);
        }

        return self::data();
    }

    /**
     * Backup the current environment file.
     */
    public static function backup(string $filename = '.env.backup'): bool
    {
        if (! self::fileExists()) {
            return false;
        }

        return File::copy(self::filePath(), base_path($filename));
    }

    /**
     * Restore the environment file from a backup.
     */
    public static function restore(string $filename = '.env.backup'): bool
    {
        $backupPath = base_path($filename);

        if (! File::exists($backupPath)) {
            return false;
        }

        return File::copy($backupPath, self::filePath());
    }

    /**
     * Remove multiple keys from the environment file.
     */

    /** @param array<int, string> $keys
     * @return Fluent<string, mixed> */
    public static function removeMultiple(array $keys): Fluent
    {
        if (! self::fileExists() || $keys === []) {
            return self::data();
        }

        $lines = self::getLines();
        $newLines = [];

        foreach ($lines as $line) {
            $shouldRemove = array_any($keys, fn (string $key): bool => (bool) preg_match('/^\s*#?\s*' . preg_quote($key, '/') . '\s*=/', (string) $line));

            if (! $shouldRemove) {
                $newLines[] = $line;
            }
        }

        self::saveLines($newLines);

        return self::data();
    }

    /**
     * Insert a raw line into the environment file.
     */
    private static function insertRawLine(string $rawLine, ?string $searchKey = null, ?string $position = null): void
    {
        $lines = self::getLines();

        if ($searchKey === null || $position === null) {
            $lines[] = $rawLine;
            self::saveLines($lines);

            return;
        }

        $newLines = [];
        $actionTaken = false;

        foreach ($lines as $line) {
            $isSearchKey = preg_match('/^\s*#?\s*' . preg_quote($searchKey, '/') . '\s*=/', (string) $line);

            if (! $actionTaken && $isSearchKey) {
                if ($position === 'before') {
                    $newLines[] = $rawLine;
                    $newLines[] = $line;
                } else {
                    $newLines[] = $line;
                    $newLines[] = $rawLine;
                }

                $actionTaken = true;
            } else {
                $newLines[] = $line;
            }
        }

        if (! $actionTaken) {
            $newLines[] = $rawLine;
        }

        self::saveLines($newLines);
    }

    /**
     * Get all lines from the environment file.
     */

    /** @return array<int, string> */
    private static function getLines(string $filename = '.env'): array
    {
        if (! self::fileExists($filename)) {
            return [];
        }

        return explode("\n", File::get(self::filePath($filename)));
    }

    /**
     * Save lines to the environment file.
     */

    /** @param array<int, string> $lines */
    private static function saveLines(array $lines, string $filename = '.env'): void
    {
        File::put(self::filePath($filename), implode("\n", $lines));
    }

    /**
     * Format a value for insertion into the environment file.
     */
    private static function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        $stringValue = is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');

        if (preg_match('/^".*"$/', $stringValue) || preg_match('/^\'.*\'$/', $stringValue)) {
            return $stringValue;
        }

        if (preg_match('/[\s#=]/', $stringValue)) {
            $stringValue = str_replace('\\"', '"', $stringValue);
            $stringValue = str_replace('"', '\\"', $stringValue);

            return '"' . $stringValue . '"';
        }

        return $stringValue;
    }

    /**
     * Strip quotes from a parsed value.
     */
    private static function stripQuotes(string $value): string
    {
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return str_replace('\\"', '"', $matches[1]);
        }

        if (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }
}
