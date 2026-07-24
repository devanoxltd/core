<?php

declare(strict_types=1);

use Devanox\Core\Helpers\InstallerInfo;
use Illuminate\Support\Facades\File;

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

it('creates install info file', function (): void {
    InstallerInfo::create(['version' => '1.0.0']);

    expect(File::exists($this->installFile))->toBeTrue()
        ->and(InstallerInfo::get('version'))->toBe('1.0.0');
});

it('returns empty data when file missing', function (): void {
    expect(InstallerInfo::data()->all())->toBe([])
        ->and(InstallerInfo::get('missing', 'default'))->toBe('default');
});

it('sets and gets values', function (): void {
    InstallerInfo::set('key', 'value');

    expect(InstallerInfo::get('key'))->toBe('value');
});

it('updates data with callback', function (): void {
    InstallerInfo::create(['counter' => 0]);

    InstallerInfo::update(function ($data): void {
        $data->set('counter', $data->get('counter', 0) + 1);
    });

    expect(InstallerInfo::get('counter'))->toBe(1);
});

it('sets and gets status', function (): void {
    InstallerInfo::setStatus(InstallerInfo::DB_CONFIGURED);

    expect(InstallerInfo::getStatus())->toBe(InstallerInfo::DB_CONFIGURED)
        ->and(InstallerInfo::getTimestamp(InstallerInfo::DB_CONFIGURED))->not->toBeNull();
});

it('sets error and retrieves message', function (): void {
    InstallerInfo::setError('Something went wrong');

    expect(InstallerInfo::getStatus())->toBe(InstallerInfo::ERROR)
        ->and(InstallerInfo::getError())->toBe('Something went wrong');
});

it('sets and gets progress', function (): void {
    InstallerInfo::setProgress(50);

    expect(InstallerInfo::getProgress())->toBe(50);
});

it('clamps progress between 0 and 100', function (): void {
    InstallerInfo::setProgress(150);
    expect(InstallerInfo::getProgress())->toBe(100);

    InstallerInfo::setProgress(-10);
    expect(InstallerInfo::getProgress())->toBe(0);
});

it('detects installed state', function (): void {
    expect(InstallerInfo::isInstalled())->toBeFalse();

    InstallerInfo::setStatus(InstallerInfo::COMPLETED);

    expect(InstallerInfo::isInstalled())->toBeTrue();
});

it('logs messages and retrieves them', function (): void {
    InstallerInfo::log('Step one complete');
    InstallerInfo::log('Step two complete', 'success');

    $logs = InstallerInfo::getLogs();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]['message'])->toBe('Step one complete')
        ->and($logs[0]['level'])->toBe('info')
        ->and($logs[1]['level'])->toBe('success');
});

it('removes install info file', function (): void {
    InstallerInfo::create(['version' => '1.0.0']);

    InstallerInfo::remove();

    expect(File::exists($this->installFile))->toBeFalse();
});
