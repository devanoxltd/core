<?php

declare(strict_types=1);

use Devanox\Core\Support\StreamingOutput;

it('streams written messages through the callback', function (): void {
    $captured = [];

    $output = new StreamingOutput(function (string $message) use (&$captured): void {
        $captured[] = $message;
    });

    $output->write('Hello ');
    $output->writeln('World');

    expect($captured)->toBe(['Hello ', 'World' . PHP_EOL]);
});

it('streams formatted messages through the callback', function (): void {
    $captured = [];

    $output = new StreamingOutput(function (string $message) use (&$captured): void {
        $captured[] = $message;
    });

    $output->writeln('<info>Success</info>');

    expect($captured)->toHaveCount(1)
        ->and($captured[0])->toContain('Success');
});
