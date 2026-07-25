<?php

declare(strict_types=1);

use Devanox\Core\Support\Module;
use Devanox\Core\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in(__DIR__);

pest()->project()->github('devanoxltd/core');

if (! isset($_SERVER['TEST_TOKEN'])) {
    $tempPath = __DIR__ . '/temp';

    if (is_dir($tempPath)) {
        (new Filesystem)->cleanDirectory($tempPath);
    }
}

if (! function_exists('createFakeModule')) {
    /**
     * Create a fake module directory structure in the workbench.
     *
     * @param  array<string, mixed>  $config
     */
    function createFakeModule(string $name, array $config = [], bool $enabled = false, ?array $directories = null): string
    {
        $modulePath = Module::path($name, true);

        if (! is_dir($modulePath)) {
            mkdir($modulePath, 0o755, true);
        }

        $configPath = $modulePath . DIRECTORY_SEPARATOR . 'Config';

        if (! is_dir($configPath)) {
            mkdir($configPath, 0o755, true);
        }

        file_put_contents($configPath . DIRECTORY_SEPARATOR . 'config.php', "<?php\n\nreturn " . var_export($config, true) . ";\n");

        if ($enabled) {
            file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'enable', '');
        }

        if ($directories !== null) {
            foreach ($directories as $directory) {
                $path = $modulePath . DIRECTORY_SEPARATOR . $directory;

                if (! is_dir($path)) {
                    mkdir($path, 0o755, true);
                }
            }
        }

        Module::clearCache();

        return $modulePath;
    }
}

if (! function_exists('deleteFakeModule')) {
    function deleteFakeModule(string $name): void
    {
        $modulePath = Module::path($name, true);

        if (is_dir($modulePath)) {
            File::deleteDirectory($modulePath);
        }

        Module::clearCache();
    }
}

if (! function_exists('cleanModulesDirectory')) {
    function cleanModulesDirectory(): void
    {
        $modulesPath = base_path(config('core.module_path', Module::MODULES_PATH));

        if (is_dir($modulesPath)) {
            File::deleteDirectory($modulesPath);
        }

        Module::clearCache();
    }
}
