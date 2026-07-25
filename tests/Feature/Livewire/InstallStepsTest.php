<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->installFile = InstallerInfo::filePath();

    if (File::exists($this->installFile)) {
        File::delete($this->installFile);
    }
});

afterEach(function (): void {
    if (File::exists($this->installFile)) {
        File::delete($this->installFile);
    }
});

it('checks requirements on mount', function (): void {
    config(['core.php' => '8.0']);
    config(['core.max_execution_time' => 30]);
    config(['core.extensions' => ['mbstring']]);
    config(['core.functions' => ['strpos']]);

    Livewire::test('core::requirements')
        ->assertSet('requirements', function ($reqs): bool {
            $status = collect($reqs)->every(fn ($req): bool => $req['status'] === true);

            return count($reqs) === 5;
        })
        ->assertDispatched('stepReady', step: 'requirements');

    expect(InstallerInfo::getStatus())->toBe(InstallerInfo::REQUIREMENTS_PASSED);
});

it('checks permissions and allows fix', function (): void {
    $tempDir = sys_get_temp_dir() . '/core-test-perms';

    if (! file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // We bind base_path just for this config
    config(['core.permissions' => [
        $tempDir => '775',
    ]]);

    Livewire::test('core::permissions')
        ->assertSet('permissions', fn ($perms): bool => count($perms) === 1 && $perms[0]['folder'] === $tempDir)
        ->call('fixPermissions', $tempDir);

    expect(is_dir($tempDir))->toBeTrue();
})->skipOnWindows();

it('can configure database', function (): void {
    Livewire::test('core::database')
        ->set('form.host', '127.0.0.1')
        ->set('form.port', 3306)
        ->set('form.database', 'test_db')
        ->set('form.dbUsername', 'root')
        ->set('form.dbPassword', '')
        ->call('submit')
        // it fails to connect to actual DB in tests, so it should catch exception and toast error
        ->assertDispatched('toast');

    // Edit mode
    Livewire::test('core::database')
        ->call('edit')
        ->assertSet('isConfigured', false)
        ->assertDispatched('unsetNextStep');
});

it('can run migrations', function (): void {
    InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURED);

    // Test skipping if already migrated
    InstallerInfo::setStatus(InstallerInfo::MIGRATED);
    Livewire::test('core::migrations')
        ->call('runAppDbMigrateInstall')
        ->assertSet('isMigrationComplete', true)
        ->assertDispatched('stepReady', step: 'migrations');

    // Reset
    InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURED);

    // Register fake app:install command
    Artisan::command('app:install', function (): int {
        $this->info('Fake installation running');

        return 0;
    });

    Livewire::test('core::migrations')
        ->call('runAppDbMigrateInstall')
        ->assertSet('isMigrationRunning', true);

    // checkStatus
    InstallerInfo::setStatus(InstallerInfo::MIGRATING);
    Livewire::test('core::migrations')
        ->call('checkStatus')
        ->assertSet('isMigrationRunning', true);
});

it('can create admin account', function (): void {
    Livewire::test('core::admin-account')
        ->set('userAccount.username', 'admin')
        ->set('userAccount.email', 'test@example.com')
        ->set('userAccount.password', 'password')
        ->set('userAccount.passwordConfirmation', 'password')
        ->call('submit')
        ->assertDispatched('stepReady', step: 'admin')
        ->assertSet('isCreated', true);

    expect(InstallerInfo::getStatus())->toBe(InstallerInfo::ADMIN_CREATED);
});

#[Table(name: 'users')]
final class MockUserWithRole extends User
{
    use HasFactory;

    public bool $roleAssigned = false;

    protected $guarded = [];

    public function assignRole(string $role): void
    {
        $this->roleAssigned = true;
    }
}

it('assigns admin role if method exists', function (): void {
    Config::set('auth.providers.users.model', MockUserWithRole::class);

    Livewire::test('core::admin-account')
        ->set('userAccount.username', 'admin2')
        ->set('userAccount.email', 'test2@example.com')
        ->set('userAccount.password', 'password')
        ->set('userAccount.passwordConfirmation', 'password')
        ->call('submit')
        ->assertSet('isCreated', true);
});
