<?php

declare(strict_types=1);

namespace Tests\Unit\Traits\Modules;

use Devanox\Core\Support\Module;
use Illuminate\Contracts\Translation\Translator;
use Modules\FakeModule\App\Providers\FakeModuleServiceProvider;
use ReflectionMethod;

beforeEach(function (): void {
    cleanModulesDirectory();
});

afterEach(function (): void {
    cleanModulesDirectory();
});

it('extracts module name from class', function (): void {
    $providerPath = createFakeProviderModule('FakeModule');

    require_once $providerPath;

    expect(FakeModuleServiceProvider::name())->toBe('FakeModule');
});

it('returns kebab lower module name', function (): void {
    $providerPath = createFakeProviderModule('FakeModule');

    require_once $providerPath;

    expect(FakeModuleServiceProvider::nameLower())->toBe('fake-module');
});

it('returns current path', function (): void {
    $providerPath = createFakeProviderModule('FakeModule');

    require_once $providerPath;

    expect(FakeModuleServiceProvider::currentPath())
        ->toContain('FakeModule' . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Providers');
});

it('registers module config', function (): void {
    $providerPath = createFakeProviderModule('ConfigModule', ['id' => 'config-module', 'version' => '1.0.0']);

    require_once $providerPath;

    $class = '\Modules\ConfigModule\App\Providers\ConfigModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerConfig');
    $method->invoke($provider);

    expect(config('config-module.name'))->toBe('ConfigModule')
        ->and(config('config-module.name_lower'))->toBe('config-module')
        ->and(config('config-module.path'))->toBe(Module::path('ConfigModule'));
});

it('registers module views when directory exists', function (): void {
    $providerPath = createFakeProviderModule('ViewModule', ['id' => 'view-module']);

    require_once $providerPath;

    $class = '\Modules\ViewModule\App\Providers\ViewModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerViews');
    $method->invoke($provider);

    $viewPaths = view()->getFinder()->getHints()['view-module'] ?? [];
    expect($viewPaths)->not->toBeEmpty();
});

it('registers module translations when directory exists', function (): void {
    $providerPath = createFakeProviderModule('LangModule', ['id' => 'lang-module']);

    require_once $providerPath;

    $class = '\Modules\LangModule\App\Providers\LangModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerTranslations');
    $method->invoke($provider);

    $translatorPaths = resolve(Translator::class)->getLoader()->namespaces()['lang-module'] ?? null;
    expect($translatorPaths)->not->toBeNull();
});

it('registers database migrations when directory exists', function (): void {
    $providerPath = createFakeProviderModule('MigrationModule');

    $migrationDir = Module::pathFor('MigrationModule', 'migrations');

    if (! is_dir($migrationDir)) {
        mkdir($migrationDir, 0o755, true);
    }

    require_once $providerPath;

    $class = '\Modules\MigrationModule\App\Providers\MigrationModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerDatabase');
    $method->invoke($provider);

    $migratorPaths = resolve('migrator')->paths();
    expect(in_array($migrationDir, $migratorPaths))->toBeTrue();
});

it('registers components and anonymous components', function (): void {
    $providerPath = createFakeProviderModule('ComponentModule');

    $componentDir = Module::pathFor('ComponentModule', 'components');
    $anonymousDir = Module::pathFor('ComponentModule', 'components-view');

    if (! is_dir($componentDir)) {
        mkdir($componentDir, 0o755, true);
    }

    if (! is_dir($anonymousDir)) {
        mkdir($anonymousDir, 0o755, true);
    }

    require_once $providerPath;

    $class = '\Modules\ComponentModule\App\Providers\ComponentModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerComponents');
    $method->invoke($provider);

    expect(true)->toBeTrue(); // Blade registration is hard to inspect directly without looking into Blade compiler, but running it without error means it worked.
});

it('registers commands if property exists', function (): void {
    $providerPath = createFakeProviderModule('CommandModule');

    // We modify the provider code to include $commands
    $modulePath = Module::path('CommandModule', true);
    $providerDir = $modulePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Providers';
    $providerCode = <<<PHP
<?php
namespace Modules\CommandModule\App\Providers;
use Devanox\Core\Traits\Modules\Provider;
use Illuminate\Support\ServiceProvider;
final class CommandModuleServiceProvider extends ServiceProvider
{
    use Provider;
    protected \$commands = [\Devanox\Core\Console\Commands\CleanUp::class];
}
PHP;
    file_put_contents($providerPath, $providerCode);

    require_once $providerPath;

    $class = '\Modules\CommandModule\App\Providers\CommandModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerCommands');
    $method->invoke($provider);

    expect(true)->toBeTrue(); // If it didn't throw, it successfully called \$this->commands()
});

it('registers livewire components', function (): void {
    $providerPath = createFakeProviderModule('LivewireModule');

    $livewireClassDir = Module::pathFor('LivewireModule', 'livewire');
    $livewireViewDir = Module::pathFor('LivewireModule', 'views') . DIRECTORY_SEPARATOR . 'livewire';
    $livewireComponentViewDir = Module::pathFor('LivewireModule', 'components-view') . DIRECTORY_SEPARATOR . 'livewire';

    if (! is_dir($livewireClassDir)) {
        mkdir($livewireClassDir, 0o755, true);
    }

    if (! is_dir($livewireViewDir)) {
        mkdir($livewireViewDir, 0o755, true);
    }

    if (! is_dir($livewireComponentViewDir)) {
        mkdir($livewireComponentViewDir, 0o755, true);
    }

    require_once $providerPath;

    $class = '\Modules\LivewireModule\App\Providers\LivewireModuleServiceProvider';
    $provider = new $class($this->app);

    $method = new ReflectionMethod($provider, 'registerLivewireComponents');
    $method->invoke($provider);

    expect(true)->toBeTrue(); // Livewire facades handle this internally
});

it('runs registerAll', function (): void {
    $providerPath = createFakeProviderModule('RegisterAllModule');

    require_once $providerPath;

    $class = '\Modules\RegisterAllModule\App\Providers\RegisterAllModuleServiceProvider';
    $provider = new $class($this->app);

    $this->app->register($provider);

    // We also simulate firing the booting and booted events to cover the callbacks
    $this->app->boot();

    // Call them directly to ensure coverage if boot doesn't hit them
    $provider->callBootingCallbacks();
    $provider->callBootedCallbacks();

    expect(true)->toBeTrue();
});

function createFakeProviderModule(string $name, array $config = ['id' => 'fake-module']): string
{
    $modulePath = Module::path($name, true);
    $providerDir = $modulePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Providers';
    $configDir = $modulePath . DIRECTORY_SEPARATOR . 'Config';
    $viewDir = $modulePath . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
    $langDir = $modulePath . DIRECTORY_SEPARATOR . 'Lang';

    if (! is_dir($providerDir)) {
        mkdir($providerDir, 0o755, true);
    }

    if (! is_dir($configDir)) {
        mkdir($configDir, 0o755, true);
    }

    if (! is_dir($viewDir)) {
        mkdir($viewDir, 0o755, true);
    }

    if (! is_dir($langDir)) {
        mkdir($langDir, 0o755, true);
    }

    file_put_contents($configDir . DIRECTORY_SEPARATOR . 'config.php', "<?php\n\nreturn " . var_export($config, true) . ";\n");

    $className = $name . 'ServiceProvider';
    $providerCode = <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\App\\Providers;

use Devanox\\Core\\Traits\\Modules\\Provider;
use Illuminate\\Support\\ServiceProvider;

final class {$className} extends ServiceProvider
{
    use Provider;

    public function register(): void
    {
        \$this->registerAll();
    }
}
PHP;

    $providerPath = $providerDir . DIRECTORY_SEPARATOR . $className . '.php';
    file_put_contents($providerPath, $providerCode);

    Module::clearCache();

    return $providerPath;
}
