<?php

declare(strict_types=1);

use Devanox\Core\Helpers\Configuration;

it('applies simple configuration values', function (): void {
    Configuration::apply(['test_key' => 'test_value']);
    expect(config('test_key'))->toBe('test_value');
});

it('applies nested configuration values using arrays', function (): void {
    Configuration::apply([
        'test_group' => [
            'sub_key' => 'sub_value',
            'nested_group' => [
                'deep_key' => 'deep_value',
            ],
        ],
    ]);

    expect(config('test_group.sub_key'))->toBe('sub_value')
        ->and(config('test_group.nested_group.deep_key'))->toBe('deep_value');
});
