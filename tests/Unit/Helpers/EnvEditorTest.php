<?php

declare(strict_types=1);

use Devanox\Core\Helpers\EnvEditor;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->envFile = '.env.test-' . uniqid();
    $this->envPath = base_path($this->envFile);

    if (File::exists($this->envPath)) {
        File::delete($this->envPath);
    }
});

afterEach(function (): void {
    if (File::exists($this->envPath)) {
        File::delete($this->envPath);
    }
});

it('checks file existence', function (): void {
    expect(EnvEditor::fileExists($this->envFile))->toBeFalse();

    File::put($this->envPath, '');

    expect(EnvEditor::fileExists($this->envFile))->toBeTrue();
});

it('returns empty data for missing file', function (): void {
    expect(EnvEditor::data(filename: $this->envFile)->all())->toBe([]);
});

it('parses simple key value pairs', function (): void {
    File::put($this->envPath, "APP_NAME=Test\nAPP_URL=https://example.com\n");

    $data = EnvEditor::data(filename: $this->envFile);

    expect($data->get('APP_NAME'))->toBe('Test')
        ->and($data->get('APP_URL'))->toBe('https://example.com');
});

it('strips quotes from values', function (): void {
    File::put($this->envPath, "APP_NAME=\"Test App\"\nAPP_KEY='base64:abc'\n");

    $data = EnvEditor::data(filename: $this->envFile);

    expect($data->get('APP_NAME'))->toBe('Test App')
        ->and($data->get('APP_KEY'))->toBe('base64:abc');
});

it('skips commented lines by default', function (): void {
    File::put($this->envPath, "# APP_NAME=Hidden\nAPP_NAME=Visible\n");

    $data = EnvEditor::data(filename: $this->envFile);

    expect($data->get('APP_NAME'))->toBe('Visible');
});

it('includes commented lines when requested', function (): void {
    File::put($this->envPath, "# APP_NAME=Hidden\n");

    $data = EnvEditor::data(includeCommented: true, filename: $this->envFile);

    expect($data->get('APP_NAME'))->toBe('Hidden');
});

it('checks if key exists', function (): void {
    File::put($this->envPath, "APP_NAME=Test\n");

    expect(EnvEditor::has('APP_NAME', filename: $this->envFile))->toBeTrue()
        ->and(EnvEditor::has('MISSING', filename: $this->envFile))->toBeFalse();
});

it('checks if key is commented', function (): void {
    File::put($this->envPath, "# APP_NAME=Test\n");

    expect(EnvEditor::isKeyCommented('APP_NAME', $this->envFile))->toBeTrue();
});

it('gets a value with default', function (): void {
    File::put($this->envPath, "APP_NAME=Test\n");

    expect(EnvEditor::get('APP_NAME', filename: $this->envFile))->toBe('Test')
        ->and(EnvEditor::get('MISSING', 'default', filename: $this->envFile))->toBe('default');
});

it('inserts a new key into missing file', function (): void {
    $data = EnvEditor::insert('APP_NAME', 'Test', $this->envFile);

    expect($data->get('APP_NAME'))->toBe('Test')
        ->and(File::get($this->envPath))->toBe('APP_NAME=Test');
});

it('inserts multiple keys', function (): void {
    EnvEditor::insertMultiple(['APP_NAME' => 'Test', 'APP_URL' => 'https://example.com'], filename: $this->envFile);

    $data = EnvEditor::data(filename: $this->envFile);

    expect($data->get('APP_NAME'))->toBe('Test')
        ->and($data->get('APP_URL'))->toBe('https://example.com');
});

it('updates existing key with insert multiple', function (): void {
    File::put($this->envPath, "APP_NAME=Old\n");

    EnvEditor::insertMultiple(['APP_NAME' => 'New'], filename: $this->envFile);

    expect(EnvEditor::get('APP_NAME', filename: $this->envFile))->toBe('New');
});

it('inserts before search key', function (): void {
    File::put($this->envPath, "APP_URL=https://example.com\n");

    EnvEditor::insertBefore('APP_URL', 'APP_NAME', 'Test', $this->envFile);

    expect(File::get($this->envPath))->toBe("APP_NAME=Test\nAPP_URL=https://example.com\n");
});

it('inserts after search key', function (): void {
    File::put($this->envPath, "APP_NAME=Test\n");

    EnvEditor::insertAfter('APP_NAME', 'APP_URL', 'https://example.com', $this->envFile);

    expect(File::get($this->envPath))->toBe("APP_NAME=Test\nAPP_URL=https://example.com\n");
});

it('comments an existing key', function (): void {
    File::put($this->envPath, "APP_NAME=Test\n");

    EnvEditor::comment('APP_NAME', $this->envFile);

    expect(EnvEditor::has('APP_NAME', filename: $this->envFile))->toBeFalse()
        ->and(EnvEditor::isKeyCommented('APP_NAME', $this->envFile))->toBeTrue();
});

it('uncomments an existing key', function (): void {
    File::put($this->envPath, "# APP_NAME=Test\n");

    EnvEditor::uncomment('APP_NAME', $this->envFile);

    expect(EnvEditor::get('APP_NAME', filename: $this->envFile))->toBe('Test');
});

it('removes a key', function (): void {
    File::put($this->envPath, "APP_NAME=Test\nAPP_URL=https://example.com\n");

    EnvEditor::remove('APP_NAME', $this->envFile);

    expect(EnvEditor::has('APP_NAME', filename: $this->envFile))->toBeFalse()
        ->and(EnvEditor::has('APP_URL', filename: $this->envFile))->toBeTrue();
});

it('removes multiple keys', function (): void {
    File::put($this->envPath, "APP_NAME=Test\nAPP_URL=https://example.com\nAPP_KEY=secret\n");

    EnvEditor::removeMultiple(['APP_NAME', 'APP_URL'], $this->envFile);

    expect(EnvEditor::has('APP_NAME', filename: $this->envFile))->toBeFalse()
        ->and(EnvEditor::has('APP_URL', filename: $this->envFile))->toBeFalse()
        ->and(EnvEditor::has('APP_KEY', filename: $this->envFile))->toBeTrue();
});

it('renames a key', function (): void {
    File::put($this->envPath, "OLD_NAME=Test\n");

    EnvEditor::renameKey('OLD_NAME', 'NEW_NAME', $this->envFile);

    expect(EnvEditor::get('NEW_NAME', filename: $this->envFile))->toBe('Test')
        ->and(EnvEditor::has('OLD_NAME', filename: $this->envFile))->toBeFalse();
});

it('adds an empty line', function (): void {
    File::put($this->envPath, "APP_NAME=Test\n");

    EnvEditor::addEmptyLine(filename: $this->envFile);

    expect(File::get($this->envPath))->toBe("APP_NAME=Test\n\n");
});

it('adds a comment line', function (): void {
    EnvEditor::addCommentLine('This is a comment', filename: $this->envFile);

    expect(File::get($this->envPath))->toContain('# This is a comment');
});

it('inserts a commented key', function (): void {
    File::put($this->envPath, "APP_URL=https://example.com\n");

    EnvEditor::insertCommented('APP_NAME', 'Test', 'APP_URL', 'before', $this->envFile);

    expect(EnvEditor::isKeyCommented('APP_NAME', $this->envFile))->toBeTrue();
});

it('removes multiple keys from file', function (): void {
    File::put($this->envPath, "APP_NAME=Test\nAPP_URL=https://example.com\n");

    EnvEditor::removeMultiple(['APP_NAME', 'APP_URL'], $this->envFile);

    expect(File::get($this->envPath))->toBeEmpty();
});

it('handles missing file in early returns', function (): void {
    expect(EnvEditor::comment('KEY', $this->envFile)->all())->toBeEmpty();
    expect(EnvEditor::uncomment('KEY', $this->envFile)->all())->toBeEmpty();
    expect(EnvEditor::renameKey('OLD', 'NEW', $this->envFile)->all())->toBeEmpty();
    expect(EnvEditor::removeMultiple(['KEY'], $this->envFile)->all())->toBeEmpty();
});

it('appends unmatched keys in insertMultiple', function (): void {
    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::insertMultiple(['EXISTING' => 2, 'NEW' => 3], 'EXISTING', 'after', $this->envFile);
    expect(File::get($this->envPath))->toContain('NEW=3');
});

it('handles moveAfter when key does not exist', function (): void {
    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::moveAfter('EXISTING', 'MISSING', $this->envFile);
    expect(File::get($this->envPath))->not->toContain('MISSING');
});

it('handles moveAfter when key is commented', function (): void {
    File::put($this->envPath, "EXISTING=1\n# TO_MOVE=2\n");
    EnvEditor::moveAfter('EXISTING', 'TO_MOVE', $this->envFile);
    $content = File::get($this->envPath);
    expect($content)->toContain("EXISTING=1\n# TO_MOVE=2");
});

it('handles moveBefore when key does not exist', function (): void {
    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::moveBefore('EXISTING', 'MISSING', $this->envFile);
    expect(File::get($this->envPath))->not->toContain('MISSING');
});

it('handles moveBefore when key is commented', function (): void {
    File::put($this->envPath, "EXISTING=1\n# TO_MOVE=2\n");
    EnvEditor::moveBefore('EXISTING', 'TO_MOVE', $this->envFile);
    $content = File::get($this->envPath);
    expect($content)->toContain("# TO_MOVE=2\nEXISTING=1");
});

it('handles removeMultiple with empty array', function (): void {
    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::removeMultiple([], $this->envFile);
    expect(File::get($this->envPath))->toContain('EXISTING=1');
});

it('inserts raw line before and after', function (): void {
    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::addCommentLine('Test', 'EXISTING', 'before', $this->envFile);
    expect(File::get($this->envPath))->toContain("# Test\nEXISTING=1");

    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::addCommentLine('Test', 'EXISTING', 'after', $this->envFile);
    expect(File::get($this->envPath))->toContain("EXISTING=1\n# Test");

    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::addCommentLine('Test', 'MISSING', 'after', $this->envFile);
    expect(File::get($this->envPath))->toContain("EXISTING=1\n\n# Test");
});

it('formats double quoted values', function (): void {
    File::put($this->envPath, "EXISTING=1\n");
    EnvEditor::insert('TEST', '"value"', $this->envFile);
    expect(File::get($this->envPath))->toContain('TEST="value"');
});

it('moves a key after another', function (): void {
    File::put($this->envPath, "APP_NAME=Test\nAPP_URL=https://example.com\n");

    EnvEditor::moveAfter('APP_NAME', 'APP_URL', $this->envFile);

    expect(File::get($this->envPath))->toBe("APP_NAME=Test\nAPP_URL=https://example.com\n");
});

it('moves a key before another', function (): void {
    File::put($this->envPath, "APP_URL=https://example.com\nAPP_NAME=Test\n");

    EnvEditor::moveBefore('APP_URL', 'APP_NAME', $this->envFile);

    expect(File::get($this->envPath))->toBe("APP_NAME=Test\nAPP_URL=https://example.com\n");
});

it('backs up and restores the env file', function (): void {
    File::put($this->envPath, "APP_NAME=Test\n");
    $backupFile = $this->envFile . '.backup';

    expect(EnvEditor::backup($backupFile, $this->envFile))->toBeTrue();

    File::put($this->envPath, "APP_NAME=Changed\n");

    expect(EnvEditor::restore($backupFile, $this->envFile))->toBeTrue()
        ->and(EnvEditor::get('APP_NAME', filename: $this->envFile))->toBe('Test');

    File::delete(base_path($backupFile));
});

it('returns false when backing up missing file', function (): void {
    $backup = '.missing-' . uniqid() . '.backup';
    $env = '.missing-' . uniqid() . '.env';

    expect(EnvEditor::backup($backup, $env))->toBeFalse();
});

it('returns false when restoring missing backup', function (): void {
    $backup = '.missing-' . uniqid() . '.backup';
    $env = '.missing-' . uniqid() . '.env';

    expect(EnvEditor::restore($backup, $env))->toBeFalse();
});

it('formats values with special characters', function (): void {
    EnvEditor::insertMultiple([
        'SPACES' => 'has spaces',
        'HASH' => 'has#hash',
        'EQUALS' => 'has=equal',
        'BOOL' => true,
        'NULL' => null,
    ], filename: $this->envFile);

    $content = File::get($this->envPath);

    expect($content)->toContain('SPACES="has spaces"')
        ->toContain('HASH="has#hash"')
        ->toContain('EQUALS="has=equal"')
        ->toContain('BOOL=true')
        ->toContain('NULL=');
});
