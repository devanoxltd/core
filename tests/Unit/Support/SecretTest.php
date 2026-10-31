<?php

declare(strict_types=1);

use Devanox\Core\Support\Secret;

it('encrypts and decrypts secrets', function (): void {
    $encrypted = Secret::encrypt('value');

    expect($encrypted)->toStartWith(Secret::PREFIX)
        ->and($encrypted)->not->toContain('value')
        ->and(Secret::decrypt($encrypted))->toBe('value');
});

it('leaves empty, null, already encrypted and plain values untouched', function (): void {
    $encrypted = Secret::encrypt('value');

    expect(Secret::encrypt(null))->toBeNull()
        ->and(Secret::encrypt(''))->toBe('')
        ->and(Secret::encrypt($encrypted))->toBe($encrypted)
        ->and(Secret::decrypt(null))->toBeNull()
        ->and(Secret::decrypt('plain'))->toBe('plain');
});

it('returns null for undecryptable secrets', function (): void {
    expect(Secret::decrypt(Secret::PREFIX . 'garbage'))->toBeNull();
});
